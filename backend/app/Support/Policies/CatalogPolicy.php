<?php

namespace App\Support\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Thin policy for catalog-style resources.
 * Child classes only declare the permission prefix (e.g. "categories").
 */
abstract class CatalogPolicy
{
    abstract protected function prefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->can($this->prefix().'.view');
    }

    public function view(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.view');
    }

    public function create(User $user): bool
    {
        return $user->can($this->prefix().'.create');
    }

    public function update(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.update');
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->can($this->prefix().'.delete');
    }
}
