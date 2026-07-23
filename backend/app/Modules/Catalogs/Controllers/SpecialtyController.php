<?php

namespace App\Modules\Catalogs\Controllers;

use App\Modules\Catalogs\Models\Specialty;
use App\Modules\Catalogs\Resources\SpecialtyResource;
use App\Modules\Catalogs\Services\SpecialtyService;
use App\Support\Http\CatalogController;
use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SpecialtyController extends CatalogController
{
    public function __construct(
        private readonly SpecialtyService $specialties,
    ) {}

    protected function service(): CatalogService
    {
        return $this->specialties;
    }

    protected function modelClass(): string
    {
        return Specialty::class;
    }

    protected function resourceClass(): string
    {
        return SpecialtyResource::class;
    }

    protected function validatedStore(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:specialties,slug'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function validatedUpdate(Request $request, Model $model): array
    {
        return $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('specialties', 'slug')->ignore($model->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
