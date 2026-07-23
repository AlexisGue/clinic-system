<?php

namespace App\Modules\Users\Services;

use App\Modules\Users\Repositories\RoleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    /** @var list<string> */
    private array $protectedRoles = ['admin'];

    public function __construct(
        private readonly RoleRepository $roles,
    ) {}

    /**
     * @param  array{search?: string, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->roles->paginate($filters);
    }

    public function find(int $id): Role
    {
        return $this->roles->findOrFail($id);
    }

    /**
     * @return Collection<int, Permission>
     */
    public function permissions(): Collection
    {
        return $this->roles->allPermissions();
    }

    /**
     * @return Collection<int, Role>
     */
    public function all(): Collection
    {
        return $this->roles->allRoles();
    }

    /**
     * @param  array{name: string, permissions?: list<string>}  $data
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($data['permissions'] ?? []);
            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return $role->load('permissions')->loadCount('users');
        });
    }

    /**
     * @param  array{name?: string, permissions?: list<string>}  $data
     */
    public function update(Role $role, array $data): Role
    {
        if (in_array($role->name, $this->protectedRoles, true) && isset($data['name']) && $data['name'] !== $role->name) {
            throw ValidationException::withMessages([
                'name' => ['El rol administrador no puede renombrarse.'],
            ]);
        }

        return DB::transaction(function () use ($role, $data) {
            if (isset($data['name'])) {
                $role->name = $data['name'];
                $role->save();
            }

            if (array_key_exists('permissions', $data)) {
                if (in_array($role->name, $this->protectedRoles, true)) {
                    throw ValidationException::withMessages([
                        'permissions' => ['Los permisos del rol administrador no se pueden modificar.'],
                    ]);
                }

                $role->syncPermissions($data['permissions'] ?? []);
            }

            app()[PermissionRegistrar::class]->forgetCachedPermissions();

            return $role->fresh()->load('permissions')->loadCount('users');
        });
    }

    public function delete(Role $role): void
    {
        if (in_array($role->name, $this->protectedRoles, true)) {
            throw ValidationException::withMessages([
                'role' => ['El rol administrador no puede eliminarse.'],
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => ['No se puede eliminar un rol asignado a usuarios.'],
            ]);
        }

        $role->delete();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
