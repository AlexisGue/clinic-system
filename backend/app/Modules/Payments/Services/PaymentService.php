<?php

namespace App\Modules\Payments\Services;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Repositories\PaymentRepository;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly SettingService $settings,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        return $this->payments->paginate($filters);
    }

    public function find(int $id): Payment
    {
        return $this->payments->findOrFail($id);
    }

    public function create(array $data): Payment
    {
        if (empty($data['consultation_id']) && empty($data['appointment_id'])) {
            throw ValidationException::withMessages([
                'consultation_id' => ['Debe indicar una consulta o una cita.'],
            ]);
        }

        PaymentMethod::query()
            ->whereKey($data['payment_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $payment = Payment::query()->create([
            'consultation_id' => $data['consultation_id'] ?? null,
            'appointment_id' => $data['appointment_id'] ?? null,
            'amount' => round((float) $data['amount'], 2),
            'currency' => strtoupper((string) ($data['currency'] ?? $this->settings->get(Setting::KEY_CURRENCY, 'USD'))),
            'payment_method_id' => $data['payment_method_id'],
            'paid_at' => $data['paid_at'] ?? now(),
            'reference' => $data['reference'] ?? null,
            'recorded_by' => Auth::id(),
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->payments->findOrFail($payment->id);
    }
}
