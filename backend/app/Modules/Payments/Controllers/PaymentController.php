<?php

namespace App\Modules\Payments\Controllers;

use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Resources\PaymentResource;
use App\Modules\Payments\Services\PaymentService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Payment::class);

        $paginator = $this->payments->list($request->only([
            'from', 'to', 'consultation_id', 'appointment_id', 'per_page',
        ]));

        return $this->paginated($paginator, PaymentResource::collection($paginator->getCollection()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Payment::class);

        $data = $request->validate([
            'consultation_id' => ['nullable', 'integer', 'exists:consultations,id'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'size:3'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = $this->payments->create($data);

        return $this->created(new PaymentResource($payment), 'Pago registrado correctamente.');
    }

    public function show(int $id): JsonResponse
    {
        $payment = $this->payments->find($id);
        $this->authorize('view', $payment);

        return $this->success(new PaymentResource($payment));
    }
}
