<?php

namespace App\Modules\Audit\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use OwenIt\Auditing\Models\Audit;

class AuditService
{
    /**
     * @param  array{user_id?: int, event?: string, auditable_type?: string, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Audit::query()
            ->with('user')
            ->latest('id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (! empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }

        if (! empty($filters['auditable_type'])) {
            $type = $filters['auditable_type'];
            $query->where(function ($q) use ($type): void {
                $q->where('auditable_type', $type)
                    ->orWhere('auditable_type', 'like', '%\\'.$type);
            });
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }
}
