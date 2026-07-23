<?php

namespace App\Modules\Doctors\Repositories;

use App\Modules\Doctors\Models\Doctor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DoctorRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Doctor::query()
            ->with(['user:id,name,email,is_active', 'specialties:id,name,slug,is_active,created_at'])
            ->latest('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('license_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $uq) use ($search): void {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): Doctor
    {
        return Doctor::query()
            ->with(['user:id,name,email,is_active', 'specialties:id,name,slug,is_active,created_at', 'schedules'])
            ->findOrFail($id);
    }

    public function options(): Collection
    {
        return Doctor::query()
            ->with(['user:id,name', 'specialties:id,name'])
            ->where('is_active', true)
            ->whereHas('user', fn (Builder $q) => $q->where('is_active', true))
            ->orderBy('id')
            ->get();
    }

    public function create(array $data): Doctor
    {
        return Doctor::query()->create($data);
    }
}
