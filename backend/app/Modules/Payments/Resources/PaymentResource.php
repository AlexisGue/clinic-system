<?php

namespace App\Modules\Payments\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'consultation_id' => $this->consultation_id,
            'appointment_id' => $this->appointment_id,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_method_id' => $this->payment_method_id,
            'payment_method' => $this->whenLoaded('paymentMethod', fn () => [
                'id' => $this->paymentMethod?->id,
                'code' => $this->paymentMethod?->code,
                'name' => $this->paymentMethod?->name,
            ]),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'reference' => $this->reference,
            'recorded_by' => $this->recorded_by,
            'recorder' => $this->whenLoaded('recorder', fn () => [
                'id' => $this->recorder?->id,
                'name' => $this->recorder?->name,
            ]),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
