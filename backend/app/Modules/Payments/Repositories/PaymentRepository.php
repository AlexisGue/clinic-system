<?php

namespace App\Modules\Payments\Repositories;

use App\Modules\Payments\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class PaymentRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Payment::query()
            ->with([
                'paymentMethod:id,code,name',
                'recorder:id,name',
                'consultation:id,folio',
                'appointment:id,folio',
            ])
            ->latest('paid_at');

        if (! empty($filters['from'])) {
            $query->where('paid_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('paid_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['consultation_id'])) {
            $query->where('consultation_id', (int) $filters['consultation_id']);
        }

        if (! empty($filters['appointment_id'])) {
            $query->where('appointment_id', (int) $filters['appointment_id']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): Payment
    {
        return Payment::query()
            ->with(['paymentMethod', 'recorder:id,name', 'consultation:id,folio', 'appointment:id,folio'])
            ->findOrFail($id);
    }
}
