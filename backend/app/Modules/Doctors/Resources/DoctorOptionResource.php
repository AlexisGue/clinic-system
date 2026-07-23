<?php

namespace App\Modules\Doctors\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Payload ligero para selects de citas/filtros. */
class DoctorOptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->whenLoaded('user', fn () => $this->user?->name),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ]),
            'license_number' => $this->license_number,
            'specialties' => $this->whenLoaded('specialties', fn () => $this->specialties->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
            ])->values()),
        ];
    }
}
