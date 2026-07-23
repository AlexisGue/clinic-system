<?php

namespace App\Modules\Prescriptions\Services;

use App\Modules\Catalogs\Models\Medicine;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Prescriptions\Exceptions\PrescriptionDomainException;
use App\Modules\Prescriptions\Models\Prescription;
use App\Modules\Prescriptions\Repositories\PrescriptionRepository;
use App\Modules\Settings\Services\SettingService;
use App\Support\Auth\ClinicActor;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PrescriptionService
{
    public function __construct(
        private readonly PrescriptionRepository $prescriptions,
        private readonly SettingService $settings,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->prescriptions->paginate($filters);
    }

    public function find(int $id): Prescription
    {
        return $this->prescriptions->findOrFail($id);
    }

    public function create(array $data): Prescription
    {
        $items = $data['items'] ?? [];

        if ($items === []) {
            throw PrescriptionDomainException::emptyItems();
        }

        return DB::transaction(function () use ($data, $items) {
            /** @var Consultation $consultation */
            $consultation = Consultation::query()->findOrFail($data['consultation_id']);

            $user = auth()->user();
            if ($user && ! ClinicActor::ownsDoctorId($user, $consultation->doctor_id)) {
                throw ValidationException::withMessages([
                    'consultation_id' => ['No puedes emitir recetas de consultas de otro médico.'],
                ]);
            }

            $prescription = Prescription::query()->create([
                'folio' => 'TMP',
                'consultation_id' => $consultation->id,
                'patient_id' => $consultation->patient_id,
                'doctor_id' => $consultation->doctor_id,
                'issued_at' => $data['issued_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'status' => Prescription::STATUS_ACTIVE,
            ]);

            $prescription->forceFill([
                'folio' => 'R-'.str_pad((string) $prescription->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            foreach ($items as $item) {
                $medicineName = $item['medicine_name'] ?? null;

                if (! empty($item['medicine_id'])) {
                    $medicine = Medicine::query()->findOrFail($item['medicine_id']);
                    $medicineName = $medicineName ?: $medicine->name;
                }

                $prescription->items()->create([
                    'medicine_id' => $item['medicine_id'] ?? null,
                    'medicine_name' => $medicineName,
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                ]);
            }

            return $this->prescriptions->findOrFail($prescription->id);
        });
    }

    public function cancel(Prescription $prescription): Prescription
    {
        if ($prescription->isCancelled()) {
            throw PrescriptionDomainException::notCancellable();
        }

        $prescription->update(['status' => Prescription::STATUS_CANCELLED]);

        return $this->prescriptions->findOrFail($prescription->id);
    }

    public function pdf(Prescription $prescription): \Barryvdh\DomPDF\PDF
    {
        $prescription->loadMissing(['patient', 'doctor.user', 'items']);

        return Pdf::loadView('prescriptions.pdf', [
            'prescription' => $prescription,
            'company' => $this->settings->company(),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ])->setPaper('letter');
    }
}
