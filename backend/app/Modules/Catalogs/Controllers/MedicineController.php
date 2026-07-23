<?php

namespace App\Modules\Catalogs\Controllers;

use App\Modules\Catalogs\Models\Medicine;
use App\Modules\Catalogs\Resources\MedicineResource;
use App\Modules\Catalogs\Services\MedicineService;
use App\Support\Http\CatalogController;
use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MedicineController extends CatalogController
{
    public function __construct(
        private readonly MedicineService $medicines,
    ) {}

    protected function service(): CatalogService
    {
        return $this->medicines;
    }

    protected function modelClass(): string
    {
        return Medicine::class;
    }

    protected function resourceClass(): string
    {
        return MedicineResource::class;
    }

    protected function validatedStore(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'presentation' => ['nullable', 'string', 'max:255'],
            'concentration' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function validatedUpdate(Request $request, Model $model): array
    {
        return $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'presentation' => ['nullable', 'string', 'max:255'],
            'concentration' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
