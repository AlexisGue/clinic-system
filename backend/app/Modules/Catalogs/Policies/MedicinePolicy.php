<?php

namespace App\Modules\Catalogs\Policies;

use App\Support\Policies\CatalogPolicy;

class MedicinePolicy extends CatalogPolicy
{
    protected function prefix(): string
    {
        return 'medicines';
    }
}
