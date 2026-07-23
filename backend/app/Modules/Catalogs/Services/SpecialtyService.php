<?php

namespace App\Modules\Catalogs\Services;

use App\Modules\Catalogs\Repositories\SpecialtyRepository;
use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SpecialtyService extends CatalogService
{
    public function __construct(SpecialtyRepository $repository)
    {
        parent::__construct($repository);
    }

    public function create(array $data): Model
    {
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return parent::create($data);
    }

    public function update(Model $model, array $data): Model
    {
        if (isset($data['name']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        return parent::update($model, $data);
    }

    protected function isInUse(Model $model): bool
    {
        return DB::table('doctor_specialty')->where('specialty_id', $model->getKey())->exists()
            || DB::table('appointments')->where('specialty_id', $model->getKey())->exists();
    }
}
