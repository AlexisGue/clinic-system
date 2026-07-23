<?php

namespace Tests\Feature\Consultations;

use App\Models\User;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConsultationFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $doctorUser;

    private User $receptionist;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->doctorUser = User::factory()->create(['name' => 'Dr. Clínica']);
        $this->doctorUser->assignRole('doctor');

        $this->doctor = Doctor::query()->create([
            'user_id' => $this->doctorUser->id,
            'license_number' => 'LIC-99',
            'is_active' => true,
        ]);

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');

        $this->patient = Patient::query()->create([
            'document_type' => 'ID',
            'document_number' => 'C-500',
            'first_name' => 'Luis',
            'last_name' => 'García',
            'is_active' => true,
        ]);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_doctor_can_create_and_finalize_consultation(): void
    {
        $create = $this->actingAs($this->doctorUser, 'web')
            ->postJson('/api/v1/consultations', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'chief_complaint' => 'Dolor de cabeza',
                'diagnosis' => 'Cefalea tensional',
                'treatment' => 'Reposo y analgésico',
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonPath('data.folio', 'C-000001');

        $id = $create->json('data.id');

        $this->actingAs($this->doctorUser, 'web')
            ->postJson("/api/v1/consultations/{$id}/finalize")
            ->assertOk()
            ->assertJsonPath('data.status', 'finalized');
    }

    public function test_receptionist_cannot_view_consultations(): void
    {
        $this->actingAs($this->receptionist, 'web')
            ->getJson('/api/v1/consultations')
            ->assertForbidden();

        $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/consultations', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'chief_complaint' => 'No debería',
            ])
            ->assertForbidden();
    }
}
