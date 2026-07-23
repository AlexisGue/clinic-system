<?php

namespace App\Modules\Patients\Services;

use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Models\PatientContact;
use App\Modules\Patients\Repositories\PatientRepository;
use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PatientService extends CatalogService
{
    public function __construct(PatientRepository $repository)
    {
        parent::__construct($repository);
    }

    public function history(Patient $patient, bool $includeClinical = false): array
    {
        $appointments = $patient->appointments()
            ->with(['doctor.user:id,name', 'specialty:id,name'])
            ->latest('starts_at')
            ->limit(50)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'folio' => $a->folio,
                'starts_at' => $a->starts_at?->toIso8601String(),
                'status' => $a->status,
                'doctor' => $a->doctor?->user?->name,
                'specialty' => $a->specialty?->name,
            ])->all();

        $consultations = $patient->consultations()
            ->with(['doctor.user:id,name'])
            ->latest('attended_at')
            ->limit(50)
            ->get()
            ->map(function ($c) use ($includeClinical) {
                $row = [
                    'id' => $c->id,
                    'folio' => $c->folio,
                    'attended_at' => $c->attended_at?->toIso8601String(),
                    'status' => $c->status,
                    'doctor' => $c->doctor?->user?->name,
                ];

                if ($includeClinical) {
                    $row['diagnosis'] = $c->diagnosis;
                }

                return $row;
            })->all();

        $prescriptions = [];
        if ($includeClinical) {
            $prescriptions = $patient->prescriptions()
                ->with(['doctor.user:id,name'])
                ->latest('issued_at')
                ->limit(50)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'folio' => $p->folio,
                    'issued_at' => $p->issued_at?->toIso8601String(),
                    'status' => $p->status,
                    'doctor' => $p->doctor?->user?->name,
                ])->all();
        }

        return [
            'appointments' => $appointments,
            'consultations' => $consultations,
            'prescriptions' => $prescriptions,
        ];
    }

    public function addContact(Patient $patient, array $data): PatientContact
    {
        return $patient->contacts()->create($data);
    }

    public function updateContact(PatientContact $contact, array $data): PatientContact
    {
        $contact->update($data);

        return $contact->fresh();
    }

    public function deleteContact(PatientContact $contact): void
    {
        $contact->delete();
    }

    protected function isInUse(Model $model): bool
    {
        return DB::table('appointments')->where('patient_id', $model->getKey())->exists()
            || DB::table('consultations')->where('patient_id', $model->getKey())->exists();
    }
}
