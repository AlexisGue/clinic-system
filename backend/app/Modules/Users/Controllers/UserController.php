<?php

namespace App\Modules\Users\Controllers;

use App\Models\User;
use App\Modules\Users\Requests\StoreUserRequest;
use App\Modules\Users\Requests\UpdateUserRequest;
use App\Modules\Users\Resources\UserResource;
use App\Modules\Users\Services\UserService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function __construct(
        private readonly UserService $users,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $paginator = $this->users->list($request->only(['search', 'role', 'is_active', 'per_page']));

        return $this->success([
            'items' => UserResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->users->create($request->validated());

        return $this->created(new UserResource($user), 'Usuario creado correctamente.');
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return $this->success(new UserResource($user->load('roles')));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $user = $this->users->update($user, $request->validated());

        return $this->success(new UserResource($user), 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->users->delete($user, $request->user());

        return $this->success(null, 'Usuario eliminado correctamente.');
    }
}
