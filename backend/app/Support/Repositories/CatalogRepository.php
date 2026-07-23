<?php

namespace App\Support\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared pagination + search for simple catalog tables.
 */
abstract class CatalogRepository
{
    abstract protected function model(): string;

    /**
     * Columns searched with LIKE when `search` is present.
     *
     * @return list<string>
     */
    abstract protected function searchable(): array;

    /**
     * @param  array{search?: string, is_active?: bool|string, per_page?: int}  $filters
     */
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        /** @var Builder $query */
        $query = $this->model()::query()->latest('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                foreach ($this->searchable() as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): Model
    {
        return $this->model()::query()->findOrFail($id);
    }

    public function create(array $data): Model
    {
        return $this->model()::query()->create($data);
    }

    /**
     * Active rows for selects/dropdowns (products form, etc.).
     *
     * @return Collection<int, Model>
     */
    public function options()
    {
        return $this->model()::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
