<?php

namespace App\Modules\Users\Services;

use App\Models\User;
use App\Modules\Users\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
    ) {}

    /**
     * @param  array{search?: string, role?: string, is_active?: bool|string, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->users->paginate($filters);
    }

    public function find(int $id): User
    {
        return $this->users->findOrFail($id);
    }

    /**
     * @param  array{name: string, email: string, password: string, is_active?: bool, roles?: list<string>}  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'is_active' => $data['is_active'] ?? true,
            ]);

            if (! empty($data['roles'])) {
                $user->syncRoles($data['roles']);
            }

            return $user->load('roles');
        });
    }

    /**
     * @param  array{name?: string, email?: string, password?: string, is_active?: bool, roles?: list<string>}  $data
     */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $payload = collect($data)->only(['name', 'email', 'is_active'])->all();

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $user->update($payload);

            if (array_key_exists('roles', $data)) {
                $user->syncRoles($data['roles'] ?? []);
            }

            return $user->fresh()->load('roles');
        });
    }

    public function delete(User $user, User $actor): void
    {
        if ($user->id === $actor->id) {
            throw ValidationException::withMessages([
                'user' => ['No puedes eliminar tu propia cuenta.'],
            ]);
        }

        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => ['Debe existir al menos un administrador en el sistema.'],
            ]);
        }

        $user->delete();
    }
}
