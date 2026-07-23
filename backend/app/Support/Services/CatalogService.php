<?php

namespace App\Support\Services;

use App\Support\Repositories\CatalogRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

abstract class CatalogService
{
    public function __construct(
        protected readonly CatalogRepository $repository,
    ) {}

    /**
     * @param  array{search?: string, is_active?: bool|string, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->repository->paginate($filters);
    }

    public function find(int $id): Model
    {
        return $this->repository->findOrFail($id);
    }

    public function options()
    {
        return $this->repository->options();
    }

    public function create(array $data): Model
    {
        return $this->repository->create($data);
    }

    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): void
    {
        if ($this->isInUse($model)) {
            throw ValidationException::withMessages([
                'resource' => ['No se puede eliminar: el registro está en uso.'],
            ]);
        }

        $model->delete();
    }

    /**
     * Override in children when the catalog is referenced by products/etc.
     */
    protected function isInUse(Model $model): bool
    {
        return false;
    }
}
