<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonPath('data.meta.total', 1);
    }

    public function test_receptionist_cannot_list_users(): void
    {
        $receptionist = User::factory()->create();
        $receptionist->assignRole('receptionist');

        $this->actingAs($receptionist, 'web')
            ->getJson('/api/v1/users')
            ->assertForbidden()
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    public function test_admin_can_create_user_with_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/users', [
                'name' => 'Recepcion Demo',
                'email' => 'recep@demo.test',
                'password' => 'Secret1234',
                'password_confirmation' => 'Secret1234',
                'is_active' => true,
                'roles' => ['receptionist'],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'recep@demo.test')
            ->assertJsonPath('data.roles.0', 'receptionist');

        $this->assertDatabaseHas('users', ['email' => 'recep@demo.test']);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/users/'.$admin->id)
            ->assertUnprocessable();
    }
}
