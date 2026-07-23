<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Modules\Settings\Models\Setting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(SettingsSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_admin_can_view_and_update_clinic_settings(): void
    {
        $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('data.settings.currency', 'USD')
            ->assertJsonPath('data.company.business_name', 'Clinic System Demo');

        $this->actingAs($this->admin, 'web')
            ->putJson('/api/v1/settings', [
                'clinic_name' => 'Mi Clínica',
                'tax_id' => 'CLI010101AAA',
                'currency' => 'USD',
                'appointment_default_duration' => 45,
                'ticket_footer' => 'Cuídese',
            ])
            ->assertOk()
            ->assertJsonPath('data.settings.clinic_name', 'Mi Clínica')
            ->assertJsonPath('data.company.ticket_footer', 'Cuídese');

        $this->assertDatabaseHas('settings', [
            'key' => Setting::KEY_CLINIC_NAME,
            'value' => 'Mi Clínica',
        ]);
    }

    public function test_doctor_can_view_but_not_update_settings(): void
    {
        $doctor = User::factory()->create();
        $doctor->assignRole('doctor');

        $this->assertFalse($doctor->fresh()->can('settings.view'));
        $this->assertFalse($doctor->fresh()->can('settings.update'));

        $this->app['auth']->forgetGuards();

        $this->actingAs($doctor, 'web')
            ->getJson('/api/v1/settings')
            ->assertForbidden();
    }

    public function test_receptionist_cannot_view_settings(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('receptionist');
        $this->app['auth']->forgetGuards();

        $this->actingAs($receptionist, 'web')
            ->getJson('/api/v1/settings')
            ->assertForbidden();
    }

    public function test_settings_cache_is_cleared_on_update(): void
    {
        Cache::put('app.settings.map', ['clinic_name' => 'Stale'], 300);

        $this->actingAs($this->admin, 'web')
            ->putJson('/api/v1/settings', [
                'clinic_name' => 'Fresh Clinic',
            ])
            ->assertOk();

        $this->actingAs($this->admin, 'web')
            ->getJson('/api/v1/settings')
            ->assertJsonPath('data.settings.clinic_name', 'Fresh Clinic');
    }
}
