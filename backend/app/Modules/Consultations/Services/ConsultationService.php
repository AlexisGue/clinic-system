<?php

namespace App\Modules\Consultations\Services;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Consultations\Exceptions\ConsultationDomainException;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Models\VitalSign;
use App\Modules\Consultations\Repositories\ConsultationRepository;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingService;
use App\Support\Auth\ClinicActor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConsultationService
{
    public function __construct(
        private readonly ConsultationRepository $consultations,
        private readonly SettingService $settings,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->consultations->paginate($filters);
    }

    public function find(int $id): Consultation
    {
        return $this->consultations->findOrFail($id);
    }

    public function create(array $data): Consultation
    {
        return DB::transaction(function () use ($data) {
            $patientId = $data['patient_id'];
            $doctorId = $data['doctor_id'];
            $appointmentId = $data['appointment_id'] ?? null;

            if ($appointmentId) {
                $appointment = Appointment::query()->lockForUpdate()->findOrFail($appointmentId);

                if ($appointment->consultation()->exists()) {
                    throw ConsultationDomainException::appointmentAlreadyLinked();
                }

                $patientId = $appointment->patient_id;
                $doctorId = $appointment->doctor_id;

                $appointment->update(['status' => Appointment::STATUS_IN_PROGRESS]);
            }

            $user = auth()->user();
            if ($user && ClinicActor::isDoctor($user) && ! ClinicActor::isAdmin($user)) {
                $ownDoctorId = ClinicActor::doctorIdFor($user);
                if ($ownDoctorId === null || (int) $doctorId !== $ownDoctorId) {
                    throw ValidationException::withMessages([
                        'doctor_id' => ['Solo puedes crear consultas a tu nombre.'],
                    ]);
                }
                $doctorId = $ownDoctorId;
            }

            $consultation = Consultation::query()->create([
                'folio' => 'TMP',
                'appointment_id' => $appointmentId,
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'attended_at' => $data['attended_at'] ?? now(),
                'chief_complaint' => $data['chief_complaint'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'treatment' => $data['treatment'] ?? null,
                'observations' => $data['observations'] ?? null,
                'status' => Consultation::STATUS_DRAFT,
            ]);

            $consultation->forceFill([
                'folio' => 'C-'.str_pad((string) $consultation->id, 6, '0', STR_PAD_LEFT),
            ])->save();

            return $this->consultations->findOrFail($consultation->id);
        });
    }

    public function update(Consultation $consultation, array $data): Consultation
    {
        if ($consultation->isFinalized()) {
            throw ConsultationDomainException::alreadyFinalized();
        }

        $consultation->update(collect($data)->only([
            'attended_at',
            'chief_complaint',
            'diagnosis',
            'treatment',
            'observations',
        ])->all());

        return $this->consultations->findOrFail($consultation->id);
    }

    public function finalize(Consultation $consultation): Consultation
    {
        if ($consultation->isFinalized()) {
            throw ConsultationDomainException::alreadyFinalized();
        }

        $requirePayment = filter_var(
            $this->settings->get(Setting::KEY_REQUIRE_PAYMENT_TO_COMPLETE, '0'),
            FILTER_VALIDATE_BOOLEAN
        );

        if ($requirePayment && ! $consultation->payments()->exists()) {
            throw ConsultationDomainException::paymentRequired();
        }

        return DB::transaction(function () use ($consultation) {
            $consultation->update([
                'status' => Consultation::STATUS_FINALIZED,
                'attended_at' => $consultation->attended_at ?? now(),
            ]);

            if ($consultation->appointment_id) {
                Appointment::query()->whereKey($consultation->appointment_id)->update([
                    'status' => Appointment::STATUS_COMPLETED,
                ]);
            }

            return $this->consultations->findOrFail($consultation->id);
        });
    }

    public function storeVitals(Consultation $consultation, array $data): VitalSign
    {
        if ($consultation->isFinalized()) {
            throw ConsultationDomainException::alreadyFinalized();
        }

        return $consultation->vitalSigns()->create([
            'recorded_at' => $data['recorded_at'] ?? now(),
            'weight_kg' => $data['weight_kg'] ?? null,
            'height_cm' => $data['height_cm'] ?? null,
            'bp_systolic' => $data['bp_systolic'] ?? null,
            'bp_diastolic' => $data['bp_diastolic'] ?? null,
            'heart_rate' => $data['heart_rate'] ?? null,
            'temperature_c' => $data['temperature_c'] ?? null,
            'spo2' => $data['spo2'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }
}
