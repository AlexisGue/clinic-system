<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Modules\Consultations\Models\Consultation;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $doctorAUser;

    private User $doctorBUser;

    private User $receptionist;

    private Doctor $doctorA;

    private Doctor $doctorB;

    private Patient $patient;

    private Consultation $consultationA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->doctorAUser = User::factory()->create(['name' => 'Dr A']);
        $this->doctorAUser->assignRole('doctor');
        $this->doctorA = Doctor::query()->create([
            'user_id' => $this->doctorAUser->id,
            'license_number' => 'A-1',
            'is_active' => true,
        ]);

        $this->doctorBUser = User::factory()->create(['name' => 'Dr B']);
        $this->doctorBUser->assignRole('doctor');
        $this->doctorB = Doctor::query()->create([
            'user_id' => $this->doctorBUser->id,
            'license_number' => 'B-1',
            'is_active' => true,
        ]);

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');

        $this->patient = Patient::query()->create([
            'document_type' => 'ID',
            'document_number' => 'SEC-1',
            'first_name' => 'Ana',
            'last_name' => 'Segura',
            'is_active' => true,
        ]);

        $this->consultationA = Consultation::query()->create([
            'folio' => 'C-000099',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorA->id,
            'attended_at' => now(),
            'diagnosis' => 'Diagnóstico confidencial',
            'status' => 'draft',
        ]);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_doctor_cannot_view_another_doctors_consultation(): void
    {
        $this->actingAs($this->doctorBUser, 'web')
            ->getJson('/api/v1/consultations/'.$this->consultationA->id)
            ->assertForbidden();
    }

    public function test_doctor_cannot_create_consultation_as_another_doctor(): void
    {
        $this->actingAs($this->doctorBUser, 'web')
            ->postJson('/api/v1/consultations', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctorA->id,
                'chief_complaint' => 'Intento indebido',
            ])
            ->assertUnprocessable();
    }

    public function test_receptionist_history_hides_diagnosis_and_prescriptions(): void
    {
        $response = $this->actingAs($this->receptionist, 'web')
            ->getJson('/api/v1/patients/'.$this->patient->id.'/history')
            ->assertOk();

        $consultations = $response->json('data.consultations');
        $this->assertNotEmpty($consultations);
        $this->assertArrayNotHasKey('diagnosis', $consultations[0]);
        $this->assertSame([], $response->json('data.prescriptions'));
    }

    public function test_doctor_history_includes_diagnosis(): void
    {
        $response = $this->actingAs($this->doctorAUser, 'web')
            ->getJson('/api/v1/patients/'.$this->patient->id.'/history')
            ->assertOk();

        $this->assertSame('Diagnóstico confidencial', $response->json('data.consultations.0.diagnosis'));
    }

    public function test_receptionist_cannot_access_consultations_report(): void
    {
        $this->actingAs($this->receptionist, 'web')
            ->getJson('/api/v1/reports/consultations')
            ->assertForbidden();
    }

    public function test_receptionist_cannot_mark_appointment_completed(): void
    {
        $appointmentId = \App\Modules\Appointments\Models\Appointment::query()->create([
            'folio' => 'A-000099',
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctorA->id,
            'starts_at' => now()->addDay()->setTime(10, 0),
            'ends_at' => now()->addDay()->setTime(10, 30),
            'status' => 'scheduled',
            'created_by' => $this->receptionist->id,
        ])->id;

        $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/appointments/'.$appointmentId.'/status', [
                'status' => 'completed',
            ])
            ->assertUnprocessable();
    }

    public function test_doctor_password_is_required_when_creating_user(): void
    {
        $this->actingAs($this->admin, 'web')
            ->postJson('/api/v1/doctors', [
                'name' => 'Nuevo Médico',
                'email' => 'nuevo.medico@test.local',
                'license_number' => 'LIC-NEW',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_admin_role_permissions_cannot_be_changed(): void
    {
        $role = Role::findByName('admin', 'web');

        $this->actingAs($this->admin, 'web')
            ->putJson('/api/v1/roles/'.$role->id, [
                'permissions' => ['dashboard.view'],
            ])
            ->assertUnprocessable();
    }
}
