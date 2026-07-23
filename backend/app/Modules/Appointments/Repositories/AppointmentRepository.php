<?php

namespace App\Modules\Appointments\Repositories;

use App\Modules\Appointments\Models\Appointment;
use App\Support\Auth\ClinicActor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class AppointmentRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = $this->filteredQuery($filters)->latest('starts_at');

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function calendar(array $filters = []): Collection
    {
        return $this->filteredQuery($filters)
            ->orderBy('starts_at')
            ->get();
    }

    public function findOrFail(int $id): Appointment
    {
        return Appointment::query()
            ->with([
                'patient:id,first_name,last_name,document_number,phone',
                'doctor.user:id,name',
                'specialty:id,name',
                'creator:id,name',
            ])
            ->findOrFail($id);
    }

    public function hasOverlap(int $doctorId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): bool
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->when($ignoreId, fn (Builder $q) => $q->where('id', '!=', $ignoreId))
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->lockForUpdate()
            ->exists();
    }

    /**
     * @param  array{from?: string, to?: string, doctor_id?: int, status?: string, patient_id?: int, search?: string, per_page?: int}  $filters
     */
    private function filteredQuery(array $filters): Builder
    {
        $query = Appointment::query()->with([
            'patient:id,first_name,last_name,document_number,phone',
            'doctor.user:id,name',
            'specialty:id,name',
        ]);

        if ($user = auth()->user()) {
            ClinicActor::scopeOwnDoctor($query, $user);
        }

        if (! empty($filters['from']) && ! empty($filters['to'])
            && Carbon::parse($filters['from'])->gt(Carbon::parse($filters['to']))) {
            [$filters['from'], $filters['to']] = [$filters['to'], $filters['from']];
        }

        if (! empty($filters['from'])) {
            $query->where('starts_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('starts_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', (int) $filters['doctor_id']);
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', (int) $filters['patient_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('reason', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $pq) use ($search): void {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }
}
