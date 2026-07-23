<?php

namespace App\Modules\Doctors\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'weekday' => $this->weekday,
            'start_time' => is_string($this->start_time) ? substr($this->start_time, 0, 5) : $this->start_time,
            'end_time' => is_string($this->end_time) ? substr($this->end_time, 0, 5) : $this->end_time,
            'slot_minutes' => $this->slot_minutes,
            'is_active' => $this->is_active,
        ];
    }
}
