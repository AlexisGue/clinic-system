<?php

namespace App\Modules\Doctors\Services;

use App\Models\User;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Doctors\Repositories\DoctorRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DoctorService
{
    public function __construct(
        private readonly DoctorRepository $doctors,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->doctors->paginate($filters);
    }

    public function find(int $id): Doctor
    {
        return $this->doctors->findOrFail($id);
    }

    public function options(): Collection
    {
        return $this->doctors->options();
    }

    public function create(array $data): Doctor
    {
        return DB::transaction(function () use ($data) {
            if (! empty($data['user_id'])) {
                $user = User::query()->findOrFail($data['user_id']);
            } else {
                if (empty($data['password'])) {
                    throw ValidationException::withMessages([
                        'password' => ['La contraseña es obligatoria al crear un médico.'],
                    ]);
                }

                $user = User::query()->create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'is_active' => true,
                ]);
            }

            if (Doctor::query()->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    'user_id' => ['Este usuario ya está vinculado a un médico.'],
                ]);
            }

            $user->assignRole('doctor');

            $doctor = $this->doctors->create([
                'user_id' => $user->id,
                'license_number' => $data['license_number'] ?? null,
                'bio' => $data['bio'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['specialty_ids'])) {
                $doctor->specialties()->sync($data['specialty_ids']);
            }

            return $this->doctors->findOrFail($doctor->id);
        });
    }

    public function update(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data) {
            $doctor->fill([
                'license_number' => array_key_exists('license_number', $data) ? $data['license_number'] : $doctor->license_number,
                'bio' => array_key_exists('bio', $data) ? $data['bio'] : $doctor->bio,
                'is_active' => array_key_exists('is_active', $data) ? $data['is_active'] : $doctor->is_active,
            ])->save();

            $userData = [];
            if (isset($data['name'])) {
                $userData['name'] = $data['name'];
            }
            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }
            if ($userData !== []) {
                $doctor->user->fill($userData)->save();
            }

            if (array_key_exists('specialty_ids', $data)) {
                $doctor->specialties()->sync($data['specialty_ids'] ?? []);
            }

            return $this->doctors->findOrFail($doctor->id);
        });
    }

    public function delete(Doctor $doctor): void
    {
        if ($doctor->appointments()->exists()) {
            throw ValidationException::withMessages([
                'resource' => ['No se puede eliminar: el médico tiene citas asociadas.'],
            ]);
        }

        DB::transaction(function () use ($doctor): void {
            $doctor->update(['is_active' => false]);

            if ($doctor->user) {
                $doctor->user->update(['is_active' => false]);
            }

            $doctor->schedules()->delete();
            $doctor->delete();
        });
    }

    public function syncSpecialties(Doctor $doctor, array $specialtyIds): Doctor
    {
        $doctor->specialties()->sync($specialtyIds);

        return $this->doctors->findOrFail($doctor->id);
    }

    public function replaceSchedules(Doctor $doctor, array $schedules): Doctor
    {
        return DB::transaction(function () use ($doctor, $schedules) {
            $doctor->schedules()->delete();

            foreach ($schedules as $row) {
                $doctor->schedules()->create([
                    'weekday' => (int) $row['weekday'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time'],
                    'slot_minutes' => (int) ($row['slot_minutes'] ?? 30),
                    'is_active' => $row['is_active'] ?? true,
                ]);
            }

            return $this->doctors->findOrFail($doctor->id);
        });
    }
}
