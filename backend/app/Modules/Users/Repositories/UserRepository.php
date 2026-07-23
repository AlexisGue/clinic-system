<?php

namespace App\Modules\Users\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * @param  array{search?: string, role?: string, is_active?: bool|string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = User::query()
            ->with('roles')
            ->latest('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['role'])) {
            $query->role($filters['role']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): User
    {
        return User::query()->with('roles')->findOrFail($id);
    }
}
