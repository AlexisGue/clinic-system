<?php

namespace App\Modules\Doctors\Resources;

use App\Modules\Catalogs\Resources\SpecialtyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'email' => $this->whenLoaded('user', fn () => $this->user?->email),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
                'is_active' => $this->user?->is_active,
            ]),
            'license_number' => $this->license_number,
            'bio' => $this->bio,
            'is_active' => $this->is_active,
            'specialties' => SpecialtyResource::collection($this->whenLoaded('specialties')),
            'schedules' => DoctorScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
