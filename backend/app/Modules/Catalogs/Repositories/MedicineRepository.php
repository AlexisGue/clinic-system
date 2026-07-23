<?php

namespace App\Modules\Catalogs\Repositories;

use App\Modules\Catalogs\Models\Medicine;
use App\Support\Repositories\CatalogRepository;

class MedicineRepository extends CatalogRepository
{
    protected function model(): string
    {
        return Medicine::class;
    }

    protected function searchable(): array
    {
        return ['name', 'presentation', 'concentration'];
    }
}
