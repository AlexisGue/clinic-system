<?php

namespace App\Modules\Doctors\Policies;

use App\Support\Policies\CatalogPolicy;

class DoctorPolicy extends CatalogPolicy
{
    protected function prefix(): string
    {
        return 'doctors';
    }
}
