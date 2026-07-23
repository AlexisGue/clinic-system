<?php

namespace App\Modules\Users\Controllers;

use App\Modules\Users\Requests\StoreRoleRequest;
use App\Modules\Users\Requests\UpdateRoleRequest;
use App\Modules\Users\Resources\PermissionResource;
use App\Modules\Users\Resources\RoleResource;
use App\Modules\Users\Services\RoleService;
use App\Support\Http\ApiController;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends ApiController
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $paginator = $this->roles->list($request->only(['search', 'per_page']));

        return $this->success([
            'items' => RoleResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        // Used by user forms: any user who can create/update users needs the role list.
        if (! $this->rolesAccessible()) {
            throw new AuthorizationException;
        }

        return $this->success(RoleResource::collection($this->roles->all()));
    }

    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return $this->success(PermissionResource::collection($this->roles->permissions()));
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roles->create($request->validated());

        return $this->created(new RoleResource($role), 'Rol creado correctamente.');
    }

    public function show(Role $role): JsonResponse
    {
        $this->authorize('view', $role);

        return $this->success(new RoleResource($role->load('permissions')->loadCount('users')));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->authorize('update', $role);

        $role = $this->roles->update($role, $request->validated());

        return $this->success(new RoleResource($role), 'Rol actualizado correctamente.');
    }

    public function destroy(Role $role): JsonResponse
    {
        $this->authorize('delete', $role);

        $this->roles->delete($role);

        return $this->success(null, 'Rol eliminado correctamente.');
    }

    private function rolesAccessible(): bool
    {
        $user = request()->user();

        return $user?->can('roles.view')
            || $user?->can('users.create')
            || $user?->can('users.update');
    }
}
