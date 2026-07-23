<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Simulate the SPA: a stateful origin so Sanctum starts a session.
        // CSRF itself is covered by Sanctum/framework tests, not ours.
        config()->set('sanctum.stateful', ['localhost:5173']);
        $this->withHeader('Referer', 'http://localhost:5173');
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Secret1234',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonStructure(['message', 'data' => ['id', 'name', 'email', 'roles', 'permissions']]);

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');

        $this->assertGuest('web');
    }

    public function test_unknown_email_returns_same_error_as_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234']);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->json('errors.email.0');

        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@demo.test',
            'password' => 'whatever123',
        ])->json('errors.email.0');

        // Identical message: no email enumeration possible.
        $this->assertSame($wrongPassword, $unknownEmail);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'Secret1234',
            'is_active' => false,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Secret1234',
        ])->assertStatus(422);

        $this->assertGuest('web');
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234']);

        foreach (range(1, 5) as $i) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429)
            ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
    }

    public function test_remember_me_is_accepted(): void
    {
        $user = User::factory()->create(['password' => 'Secret1234']);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Secret1234',
            'remember' => true,
        ])->assertOk();

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotNull($user->fresh()->remember_token);
    }

    public function test_branding_is_public(): void
    {
        $this->getJson('/api/v1/auth/branding')
            ->assertOk()
            ->assertJsonStructure(['data' => ['clinic_name', 'tagline']]);
    }

    public function test_guest_cannot_fetch_profile(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertGuest('web');
    }
}
