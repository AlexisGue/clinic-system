<?php

namespace App\Modules\Prescriptions\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'consultation_id' => $this->consultation_id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'notes' => $this->notes,
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
            'items' => PrescriptionItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
