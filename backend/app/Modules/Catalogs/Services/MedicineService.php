<?php

namespace App\Modules\Catalogs\Services;

use App\Modules\Catalogs\Repositories\MedicineRepository;
use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MedicineService extends CatalogService
{
    public function __construct(MedicineRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function isInUse(Model $model): bool
    {
        return DB::table('prescription_items')->where('medicine_id', $model->getKey())->exists();
    }
}
