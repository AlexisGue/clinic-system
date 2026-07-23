<?php

namespace App\Modules\Patients\Policies;

use App\Support\Policies\CatalogPolicy;

class PatientPolicy extends CatalogPolicy
{
    protected function prefix(): string
    {
        return 'patients';
    }
}
