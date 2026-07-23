<?php

namespace App\Modules\Patients\Repositories;

use App\Modules\Patients\Models\Patient;
use App\Support\Repositories\CatalogRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PatientRepository extends CatalogRepository
{
    protected function model(): string
    {
        return Patient::class;
    }

    protected function searchable(): array
    {
        return ['first_name', 'last_name', 'document_number', 'email', 'phone'];
    }

    public function findOrFail(int $id): Model
    {
        return Patient::query()->with('contacts')->findOrFail($id);
    }

    public function options(): Collection
    {
        return Patient::query()
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }

    public function paginate(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Patient::query()->latest('id');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                foreach ($this->searchable() as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
                $q->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$search}%"]);
            });
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }
}
