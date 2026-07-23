<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

/**
 * Spatie's Role::users() resolves the related model via getModelForGuard().
 * During withCount()/loadCount() that can receive a null class and blow up
 * with "Class name must be a valid object or a string". Pin User explicitly.
 */
class Role extends SpatieRole
{
    public function users(): BelongsToMany
    {
        return $this->morphedByMany(
            User::class,
            'model',
            config('permission.table_names.model_has_roles'),
            app(PermissionRegistrar::class)->pivotRole,
            config('permission.column_names.model_morph_key')
        );
    }
}
