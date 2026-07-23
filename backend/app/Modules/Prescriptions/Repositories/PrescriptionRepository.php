<?php

namespace App\Modules\Prescriptions\Repositories;

use App\Modules\Prescriptions\Models\Prescription;
use App\Support\Auth\ClinicActor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class PrescriptionRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Prescription::query()
            ->with([
                'patient:id,first_name,last_name,document_number',
                'doctor.user:id,name',
                'items',
            ])
            ->latest('issued_at');

        if ($user = auth()->user()) {
            ClinicActor::scopeOwnDoctor($query, $user);
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', (int) $filters['patient_id']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', (int) $filters['doctor_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $pq) use ($search): void {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): Prescription
    {
        return Prescription::query()
            ->with([
                'patient',
                'doctor.user:id,name',
                'consultation:id,folio',
                'items.medicine:id,name',
            ])
            ->findOrFail($id);
    }
}
