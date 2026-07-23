<?php

namespace App\Modules\Doctors\Controllers;

use App\Modules\Doctors\Models\Doctor;
use App\Modules\Doctors\Resources\DoctorOptionResource;
use App\Modules\Doctors\Resources\DoctorResource;
use App\Modules\Doctors\Resources\DoctorScheduleResource;
use App\Modules\Doctors\Services\DoctorService;
use App\Support\Http\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class DoctorController extends ApiController
{
    public function __construct(
        private readonly DoctorService $doctors,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Doctor::class);

        $paginator = $this->doctors->list($request->only(['search', 'is_active', 'per_page']));

        return $this->paginated($paginator, DoctorResource::collection($paginator->getCollection()));
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Doctor::class);

        return $this->success(DoctorOptionResource::collection($this->doctors->options()));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Doctor::class);

        $data = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:doctors,user_id'],
            'name' => ['required_without:user_id', 'string', 'max:255'],
            'email' => ['required_without:user_id', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required_without:user_id', 'nullable', 'string', Password::defaults()],
            'license_number' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'specialty_ids' => ['sometimes', 'array'],
            'specialty_ids.*' => ['integer', 'exists:specialties,id'],
        ]);

        $doctor = $this->doctors->create($data);

        return $this->created(new DoctorResource($doctor), 'Médico creado correctamente.');
    }

    public function show(int $id): JsonResponse
    {
        $doctor = $this->doctors->find($id);
        $this->authorize('view', $doctor);

        return $this->success(new DoctorResource($doctor));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $doctor = $this->doctors->find($id);
        $this->authorize('update', $doctor);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($doctor->user_id)],
            'license_number' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'specialty_ids' => ['sometimes', 'array'],
            'specialty_ids.*' => ['integer', 'exists:specialties,id'],
        ]);

        $doctor = $this->doctors->update($doctor, $data);

        return $this->success(new DoctorResource($doctor), 'Médico actualizado correctamente.');
    }

    public function destroy(int $id): JsonResponse
    {
        $doctor = $this->doctors->find($id);
        $this->authorize('delete', $doctor);

        $this->doctors->delete($doctor);

        return $this->success(null, 'Médico eliminado correctamente.');
    }

    public function syncSpecialties(Request $request, int $id): JsonResponse
    {
        $doctor = $this->doctors->find($id);
        $this->authorize('update', $doctor);

        $data = $request->validate([
            'specialty_ids' => ['required', 'array'],
            'specialty_ids.*' => ['integer', 'exists:specialties,id'],
        ]);

        $doctor = $this->doctors->syncSpecialties($doctor, $data['specialty_ids']);

        return $this->success(new DoctorResource($doctor), 'Especialidades actualizadas.');
    }

    public function schedules(int $id): JsonResponse
    {
        $doctor = $this->doctors->find($id);
        $this->authorize('view', $doctor);

        return $this->success(DoctorScheduleResource::collection($doctor->schedules));
    }

    public function updateSchedules(Request $request, int $id): JsonResponse
    {
        $doctor = $this->doctors->find($id);
        $this->authorize('update', $doctor);

        $data = $request->validate([
            'schedules' => ['required', 'array'],
            'schedules.*.weekday' => ['required', 'integer', 'between:0,6'],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i', 'after:schedules.*.start_time'],
            'schedules.*.slot_minutes' => ['sometimes', 'integer', 'min:5', 'max:240'],
            'schedules.*.is_active' => ['sometimes', 'boolean'],
        ]);

        $doctor = $this->doctors->replaceSchedules($doctor, $data['schedules']);

        return $this->success(new DoctorResource($doctor), 'Horarios actualizados.');
    }
}
