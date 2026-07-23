<?php

namespace App\Modules\Appointments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'folio' => $this->folio,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'specialty_id' => $this->specialty_id,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'ends_at' => $this->ends_at?->toIso8601String(),
            'status' => $this->status,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'created_by' => $this->created_by,
            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient?->id,
                'full_name' => trim(($this->patient?->first_name ?? '').' '.($this->patient?->last_name ?? '')),
                'document_number' => $this->patient?->document_number,
                'phone' => $this->patient?->phone,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor?->id,
                'name' => $this->doctor?->user?->name,
            ]),
            'specialty' => $this->whenLoaded('specialty', fn () => $this->specialty ? [
                'id' => $this->specialty->id,
                'name' => $this->specialty->name,
            ] : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
