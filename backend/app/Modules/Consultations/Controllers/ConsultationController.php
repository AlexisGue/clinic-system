<?php

namespace App\Modules\Consultations\Controllers;

use App\Modules\Consultations\Models\Consultation;
use App\Modules\Consultations\Resources\ConsultationResource;
use App\Modules\Consultations\Resources\VitalSignResource;
use App\Modules\Consultations\Services\ConsultationService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConsultationController extends ApiController
{
    public function __construct(
        private readonly ConsultationService $consultations,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Consultation::class);

        $paginator = $this->consultations->list($request->only([
            'from', 'to', 'doctor_id', 'patient_id', 'status', 'search', 'per_page',
        ]));

        return $this->paginated($paginator, ConsultationResource::collection($paginator->getCollection()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Consultation::class);

        $data = $request->validate([
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'patient_id' => ['required_without:appointment_id', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required_without:appointment_id', 'integer', 'exists:doctors,id'],
            'attended_at' => ['nullable', 'date'],
            'chief_complaint' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
        ]);

        $consultation = $this->consultations->create($data);

        return $this->created(new ConsultationResource($consultation), 'Consulta creada correctamente.');
    }

    public function show(int $id): JsonResponse
    {
        $consultation = $this->consultations->find($id);
        $this->authorize('view', $consultation);

        return $this->success(new ConsultationResource($consultation));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $consultation = $this->consultations->find($id);
        $this->authorize('update', $consultation);

        $data = $request->validate([
            'attended_at' => ['nullable', 'date'],
            'chief_complaint' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'treatment' => ['nullable', 'string'],
            'observations' => ['nullable', 'string'],
        ]);

        $consultation = $this->consultations->update($consultation, $data);

        return $this->success(new ConsultationResource($consultation), 'Consulta actualizada correctamente.');
    }

    public function finalize(int $id): JsonResponse
    {
        $consultation = $this->consultations->find($id);
        $this->authorize('finalize', $consultation);

        $consultation = $this->consultations->finalize($consultation);

        return $this->success(new ConsultationResource($consultation), 'Consulta finalizada correctamente.');
    }

    public function storeVitals(Request $request, int $id): JsonResponse
    {
        $consultation = $this->consultations->find($id);
        $this->authorize('update', $consultation);

        $data = $request->validate([
            'recorded_at' => ['nullable', 'date'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'height_cm' => ['nullable', 'numeric', 'min:0'],
            'bp_systolic' => ['nullable', 'integer', 'min:0'],
            'bp_diastolic' => ['nullable', 'integer', 'min:0'],
            'heart_rate' => ['nullable', 'integer', 'min:0'],
            'temperature_c' => ['nullable', 'numeric'],
            'spo2' => ['nullable', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $vitals = $this->consultations->storeVitals($consultation, $data);

        return $this->created(new VitalSignResource($vitals), 'Signos vitales registrados.');
    }
}
