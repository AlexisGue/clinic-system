<?php

namespace Tests\Feature\Patients;

use App\Models\User;
use App\Modules\Patients\Models\Patient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $receptionist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->receptionist = User::factory()->create();
        $this->receptionist->assignRole('receptionist');

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_receptionist_can_create_and_list_patients(): void
    {
        $create = $this->actingAs($this->receptionist, 'web')
            ->postJson('/api/v1/patients', [
                'document_type' => 'ID',
                'document_number' => 'P-1001',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'phone' => '555-0101',
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('data.document_number', 'P-1001')
            ->assertJsonPath('data.full_name', 'Juan Pérez');

        $this->actingAs($this->receptionist, 'web')
            ->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_can_manage_patient_contacts(): void
    {
        $patient = Patient::query()->create([
            'document_type' => 'ID',
            'document_number' => 'P-2002',
            'first_name' => 'María',
            'last_name' => 'López',
            'is_active' => true,
        ]);

        $this->actingAs($this->receptionist, 'web')
            ->postJson("/api/v1/patients/{$patient->id}/contacts", [
                'name' => 'Carlos López',
                'relationship' => 'Esposo',
                'phone' => '555-9999',
                'is_emergency' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Carlos López');

        $this->actingAs($this->receptionist, 'web')
            ->getJson("/api/v1/patients/{$patient->id}/contacts")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_only_admin_can_delete_patient(): void
    {
        $patient = Patient::query()->create([
            'document_type' => 'ID',
            'document_number' => 'P-3003',
            'first_name' => 'Ana',
            'last_name' => 'Ruiz',
            'is_active' => true,
        ]);

        $this->actingAs($this->receptionist, 'web')
            ->deleteJson("/api/v1/patients/{$patient->id}")
            ->assertForbidden();

        $this->app['auth']->forgetGuards();

        $this->actingAs($this->admin, 'web')
            ->deleteJson("/api/v1/patients/{$patient->id}")
            ->assertOk();
    }
}
