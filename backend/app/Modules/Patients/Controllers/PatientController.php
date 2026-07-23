<?php

namespace App\Modules\Patients\Controllers;

use App\Modules\Patients\Models\Patient;
use App\Modules\Patients\Models\PatientContact;
use App\Modules\Patients\Resources\PatientContactResource;
use App\Modules\Patients\Resources\PatientOptionResource;
use App\Modules\Patients\Resources\PatientResource;
use App\Modules\Patients\Services\PatientService;
use App\Support\Http\CatalogController;
use App\Support\Services\CatalogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PatientController extends CatalogController
{
    public function __construct(
        private readonly PatientService $patients,
    ) {}

    protected function service(): CatalogService
    {
        return $this->patients;
    }

    protected function modelClass(): string
    {
        return Patient::class;
    }

    protected function resourceClass(): string
    {
        return PatientResource::class;
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Patient::class);

        return $this->success(PatientOptionResource::collection($this->patients->options()));
    }

    public function history(Request $request, int $id): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->patients->find($id);
        $this->authorize('view', $patient);

        return $this->success($this->patients->history(
            $patient,
            (bool) $request->user()?->can('consultations.view')
        ));
    }

    public function contacts(int $id): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->patients->find($id);
        $this->authorize('view', $patient);

        return $this->success(PatientContactResource::collection($patient->contacts));
    }

    public function storeContact(Request $request, int $id): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->patients->find($id);
        $this->authorize('update', $patient);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_emergency' => ['sometimes', 'boolean'],
        ]);

        $contact = $this->patients->addContact($patient, $data);

        return $this->created(new PatientContactResource($contact), 'Contacto agregado correctamente.');
    }

    public function updateContact(Request $request, int $id, int $contact): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->patients->find($id);
        $this->authorize('update', $patient);

        $model = PatientContact::query()
            ->where('patient_id', $patient->id)
            ->whereKey($contact)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'relationship' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_emergency' => ['sometimes', 'boolean'],
        ]);

        $model = $this->patients->updateContact($model, $data);

        return $this->success(new PatientContactResource($model), 'Contacto actualizado correctamente.');
    }

    public function destroyContact(int $id, int $contact): JsonResponse
    {
        /** @var Patient $patient */
        $patient = $this->patients->find($id);
        $this->authorize('update', $patient);

        $model = PatientContact::query()
            ->where('patient_id', $patient->id)
            ->whereKey($contact)
            ->firstOrFail();

        $this->patients->deleteContact($model);

        return $this->success(null, 'Contacto eliminado correctamente.');
    }

    protected function validatedStore(Request $request): array
    {
        return $request->validate([
            'document_type' => ['required', 'string', 'max:20'],
            'document_number' => ['required', 'string', 'max:50', 'unique:patients,document_number'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }

    protected function validatedUpdate(Request $request, Model $model): array
    {
        return $request->validate([
            'document_type' => ['sometimes', 'required', 'string', 'max:20'],
            'document_number' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('patients', 'document_number')->ignore($model->id),
            ],
            'first_name' => ['sometimes', 'required', 'string', 'max:255'],
            'last_name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'allergies' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
    }
}
