<?php

namespace App\Modules\Appointments\Controllers;

use App\Modules\Appointments\Models\Appointment;
use App\Modules\Appointments\Resources\AppointmentResource;
use App\Modules\Appointments\Services\AppointmentService;
use App\Support\Auth\ClinicActor;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AppointmentController extends ApiController
{
    public function __construct(
        private readonly AppointmentService $appointments,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $paginator = $this->appointments->list($request->only([
            'from', 'to', 'doctor_id', 'patient_id', 'status', 'search', 'per_page',
        ]));

        return $this->paginated($paginator, AppointmentResource::collection($paginator->getCollection()));
    }

    public function calendar(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Appointment::class);

        $items = $this->appointments->calendar($request->only([
            'from', 'to', 'doctor_id', 'status',
        ]));

        return $this->success(AppointmentResource::collection($items));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Appointment::class);

        $data = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $appointment = $this->appointments->create($data);

        return $this->created(new AppointmentResource($appointment), 'Cita creada correctamente.');
    }

    public function show(int $id): JsonResponse
    {
        $appointment = $this->appointments->find($id);
        $this->authorize('view', $appointment);

        return $this->success(new AppointmentResource($appointment));
    }

    public function reschedule(Request $request, int $id): JsonResponse
    {
        $appointment = $this->appointments->find($id);
        $this->authorize('reschedule', $appointment);

        $data = $request->validate([
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
        ]);

        $appointment = $this->appointments->reschedule($appointment, $data);

        return $this->success(new AppointmentResource($appointment), 'Cita reprogramada correctamente.');
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $appointment = $this->appointments->find($id);
        $this->authorize('cancel', $appointment);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $appointment = $this->appointments->cancel($appointment, $data['reason']);

        return $this->success(new AppointmentResource($appointment), 'Cita cancelada correctamente.');
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $appointment = $this->appointments->find($id);
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,in_progress,no_show,completed'],
        ]);

        $user = $request->user();
        $status = $data['status'];
        $receptionistAllowed = ['confirmed', 'no_show'];

        if ($user
            && ! ClinicActor::isAdmin($user)
            && ! ClinicActor::isDoctor($user)
            && ! in_array($status, $receptionistAllowed, true)
        ) {
            throw ValidationException::withMessages([
                'status' => ['Recepción solo puede marcar confirmada o no asistió.'],
            ]);
        }

        $appointment = $this->appointments->updateStatus($appointment, $status);

        return $this->success(new AppointmentResource($appointment), 'Estado de cita actualizado.');
    }
}
