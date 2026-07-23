<?php

namespace Tests\Feature\Users;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleManagementTest extends TestCase
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

    public function test_admin_can_create_and_update_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $create = $this->actingAs($admin, 'web')
            ->postJson('/api/v1/roles', [
                'name' => 'supervisor',
                'permissions' => ['patients.view', 'appointments.view'],
            ]);

        $create
            ->assertCreated()
            ->assertJsonPath('data.name', 'supervisor');

        $roleId = $create->json('data.id');

        $this->actingAs($admin, 'web')
            ->putJson('/api/v1/roles/'.$roleId, [
                'permissions' => ['patients.view', 'appointments.view', 'appointments.create'],
            ])
            ->assertOk()
            ->assertJsonCount(3, 'data.permissions');
    }

    public function test_admin_can_list_roles_with_users_count(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin, 'web')
            ->getJson('/api/v1/roles')
            ->assertOk()
            ->assertJsonPath('data.items.0.name', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'items' => [
                        ['id', 'name', 'permissions', 'users_count'],
                    ],
                ],
            ]);
    }

    public function test_admin_role_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $role = Role::findByName('admin', 'web');

        $this->actingAs($admin, 'web')
            ->deleteJson('/api/v1/roles/'.$role->id)
            ->assertUnprocessable();
    }
}
