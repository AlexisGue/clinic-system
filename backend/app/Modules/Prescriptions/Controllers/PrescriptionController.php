<?php

namespace App\Modules\Prescriptions\Controllers;

use App\Modules\Prescriptions\Models\Prescription;
use App\Modules\Prescriptions\Resources\PrescriptionResource;
use App\Modules\Prescriptions\Services\PrescriptionService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrescriptionController extends ApiController
{
    public function __construct(
        private readonly PrescriptionService $prescriptions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Prescription::class);

        $paginator = $this->prescriptions->list($request->only([
            'patient_id', 'doctor_id', 'status', 'search', 'per_page',
        ]));

        return $this->paginated($paginator, PrescriptionResource::collection($paginator->getCollection()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Prescription::class);

        $data = $request->validate([
            'consultation_id' => ['required', 'integer', 'exists:consultations,id'],
            'issued_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.medicine_id' => ['nullable', 'integer', 'exists:medicines,id'],
            'items.*.medicine_name' => ['required_without:items.*.medicine_id', 'string', 'max:255'],
            'items.*.dosage' => ['nullable', 'string', 'max:255'],
            'items.*.frequency' => ['nullable', 'string', 'max:255'],
            'items.*.duration' => ['nullable', 'string', 'max:255'],
            'items.*.instructions' => ['nullable', 'string'],
        ]);

        $prescription = $this->prescriptions->create($data);

        return $this->created(new PrescriptionResource($prescription), 'Receta creada correctamente.');
    }

    public function show(int $id): JsonResponse
    {
        $prescription = $this->prescriptions->find($id);
        $this->authorize('view', $prescription);

        return $this->success(new PrescriptionResource($prescription));
    }

    public function cancel(int $id): JsonResponse
    {
        $prescription = $this->prescriptions->find($id);
        $this->authorize('cancel', $prescription);

        $prescription = $this->prescriptions->cancel($prescription);

        return $this->success(new PrescriptionResource($prescription), 'Receta cancelada correctamente.');
    }

    public function pdf(int $id): Response
    {
        $prescription = $this->prescriptions->find($id);
        $this->authorize('export', $prescription);

        return $this->prescriptions->pdf($prescription)
            ->download("receta-{$prescription->folio}.pdf");
    }
}
