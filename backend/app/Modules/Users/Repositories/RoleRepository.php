<?php

namespace App\Modules\Users\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;
use App\Models\Role;

class RoleRepository
{
    /**
     * @param  array{search?: string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Role::query()
            ->with('permissions')
            ->withCount('users')
            ->orderBy('name');

        if (! empty($filters['search'])) {
            $query->where('name', 'like', '%'.$filters['search'].'%');
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): Role
    {
        return Role::query()->with('permissions')->withCount('users')->findOrFail($id);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function allPermissions(): Collection
    {
        return Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
    }

    /**
     * @return Collection<int, Role>
     */
    public function allRoles(): Collection
    {
        return Role::query()->where('guard_name', 'web')->orderBy('name')->get();
    }
}
