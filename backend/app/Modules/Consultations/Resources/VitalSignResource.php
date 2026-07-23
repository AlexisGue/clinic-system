<?php

namespace App\Modules\Consultations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VitalSignResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consultation_id' => $this->consultation_id,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'weight_kg' => $this->weight_kg !== null ? (float) $this->weight_kg : null,
            'height_cm' => $this->height_cm !== null ? (float) $this->height_cm : null,
            'bp_systolic' => $this->bp_systolic,
            'bp_diastolic' => $this->bp_diastolic,
            'heart_rate' => $this->heart_rate,
            'temperature_c' => $this->temperature_c !== null ? (float) $this->temperature_c : null,
            'spo2' => $this->spo2,
            'notes' => $this->notes,
        ];
    }
}
