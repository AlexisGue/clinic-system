<?php

namespace App\Modules\Catalogs\Repositories;

use App\Modules\Catalogs\Models\Specialty;
use App\Support\Repositories\CatalogRepository;

class SpecialtyRepository extends CatalogRepository
{
    protected function model(): string
    {
        return Specialty::class;
    }

    protected function searchable(): array
    {
        return ['name', 'slug'];
    }
}
