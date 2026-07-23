<?php

namespace App\Modules\Patients\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'name' => $this->name,
            'relationship' => $this->relationship,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_emergency' => $this->is_emergency,
        ];
    }
}
