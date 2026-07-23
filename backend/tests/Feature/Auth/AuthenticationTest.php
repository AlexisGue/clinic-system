<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Sanctum only starts the session for first-party SPA requests.
     * Mimic the Vue app Origin so EnsureFrontendRequestsAreStateful
     * attaches StartSession + CSRF (same path the browser takes).
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeaders([
            'Origin' => 'http://localhost:5173',
            'Referer' => 'http://localhost:5173/',
        ]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'seller@demo.test',
            'password' => 'Secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'seller@demo.test',
            'password' => 'Secret1234',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.email', 'seller@demo.test')
            ->assertJsonPath('message', 'Sesión iniciada correctamente.');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'seller@demo.test',
            'password' => 'Secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'seller@demo.test',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest('web');
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'email' => 'blocked@demo.test',
            'password' => 'Secret1234',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'blocked@demo.test',
            'password' => 'Secret1234',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertGuest('web');
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_authenticated_user_can_fetch_profile_and_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'web')
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->actingAs($user, 'web')
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');

        $this->assertGuest('web');
    }
}
