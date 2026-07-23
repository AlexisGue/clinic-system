<?php

namespace App\Modules\Consultations\Repositories;

use App\Modules\Consultations\Models\Consultation;
use App\Support\Auth\ClinicActor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ConsultationRepository
{
    public function paginate(array $filters = []): LengthAwarePaginator
    {
        $query = Consultation::query()
            ->with([
                'patient:id,first_name,last_name,document_number',
                'doctor.user:id,name',
                'appointment:id,folio,starts_at',
            ])
            ->latest('id');

        if ($user = auth()->user()) {
            ClinicActor::scopeOwnDoctor($query, $user);
        }

        if (! empty($filters['from'])) {
            $query->where('attended_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('attended_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['doctor_id'])) {
            $query->where('doctor_id', (int) $filters['doctor_id']);
        }

        if (! empty($filters['patient_id'])) {
            $query->where('patient_id', (int) $filters['patient_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('folio', 'like', "%{$search}%")
                    ->orWhere('diagnosis', 'like', "%{$search}%")
                    ->orWhereHas('patient', function (Builder $pq) use ($search): void {
                        $pq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findOrFail(int $id): Consultation
    {
        return Consultation::query()
            ->with([
                'patient',
                'doctor.user:id,name',
                'appointment',
                'vitalSigns',
                'prescriptions.items',
            ])
            ->findOrFail($id);
    }
}
