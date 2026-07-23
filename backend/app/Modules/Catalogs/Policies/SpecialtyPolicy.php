<?php

namespace App\Modules\Catalogs\Policies;

use App\Support\Policies\CatalogPolicy;

class SpecialtyPolicy extends CatalogPolicy
{
    protected function prefix(): string
    {
        return 'specialties';
    }
}
