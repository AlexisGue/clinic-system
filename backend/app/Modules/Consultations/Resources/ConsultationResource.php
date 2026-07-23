<?php

namespace App\Modules\Consultations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'appointment_id' => $this->appointment_id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'attended_at' => $this->attended_at?->toIso8601String(),
            'chief_complaint' => $this->chief_complaint,
            'diagnosis' => $this->diagnosis,
            'treatment' => $this->treatment,
            'observations' => $this->observations,
            'status' => $this->status,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient?->id,
                'full_name' => trim(($this->patient?->first_name ?? '').' '.($this->patient?->last_name ?? '')),
                'document_number' => $this->patient?->document_number,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor?->id,
                'name' => $this->doctor?->user?->name,
            ]),
            'vital_signs' => VitalSignResource::collection($this->whenLoaded('vitalSigns')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
