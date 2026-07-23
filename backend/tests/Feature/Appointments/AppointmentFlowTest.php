<?php

namespace Tests\Feature\Appointments;

use App\Models\User;
use App\Modules\Doctors\Models\Doctor;
use App\Modules\Patients\Models\Patient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $receptionist;

    private Doctor $doctor;

    private Patient $patient;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');

        $doctorUser = User::factory()->create(['name' => 'Dr. Test']);
        $doctorUser->assignRole('doctor');

        $this->doctor = Doctor::query()->create([
            'user_id' => $doctorUser->id,
            'license_number' => 'LIC-1',
            'is_active' => true,
        ]);

        // Always pick next Monday so weekday=1 matches schedule.
        $monday = Carbon::now()->next(Carbon::MONDAY)->setTime(9, 0);

        $this->doctor->schedules()->create([
            'weekday' => Carbon::MONDAY,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'slot_minutes' => 30,
            'is_active' => true,
        ]);

        $this->patient = Patient::query()->create([
            'document_type' => 'ID',
            'document_number' => 'A-100',
            'first_name' => 'Paciente',
            'last_name' => 'Prueba',
            'is_active' => true,
        ]);

        Carbon::setTestNow($monday->copy()->subDay()); // Sunday before

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_can_create_appointment_within_schedule(): void
    {
        $starts = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        $ends = $starts->copy()->addMinutes(30);

        $response = $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'starts_at' => $starts->toIso8601String(),
                'ends_at' => $ends->toIso8601String(),
                'reason' => 'Control',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.folio', 'A-000001');
    }

    public function test_overlap_is_blocked(): void
    {
        $starts = Carbon::now()->next(Carbon::MONDAY)->setTime(10, 0);
        $ends = $starts->copy()->addMinutes(30);

        $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'starts_at' => $starts->toIso8601String(),
                'ends_at' => $ends->toIso8601String(),
            ])
            ->assertCreated();

        $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'starts_at' => $starts->copy()->addMinutes(15)->toIso8601String(),
                'ends_at' => $ends->copy()->addMinutes(15)->toIso8601String(),
            ])
            ->assertStatus(422)
            ->assertJsonPath('code', 'APPOINTMENT_OVERLAP');
    }

    public function test_can_cancel_appointment(): void
    {
        $starts = Carbon::now()->next(Carbon::MONDAY)->setTime(11, 0);
        $ends = $starts->copy()->addMinutes(30);

        $id = $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/appointments', [
                'patient_id' => $this->patient->id,
                'doctor_id' => $this->doctor->id,
                'starts_at' => $starts->toIso8601String(),
                'ends_at' => $ends->toIso8601String(),
            ])
            ->json('data.id');

        $this->actingAs($this->receptionist, 'web')
            ->postJson("/api/v1/appointments/{$id}/cancel", [
                'reason' => 'Paciente no disponible',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }
}
