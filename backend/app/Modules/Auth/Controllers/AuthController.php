<?php

namespace App\Modules\Auth\Controllers;

use App\Modules\Auth\Requests\LoginRequest;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Settings\Services\SettingService;
use App\Modules\Users\Resources\UserResource;
use App\Support\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use ApiResponses;

    public function __construct(
        private readonly AuthService $authService,
        private readonly SettingService $settings,
    ) {}

    public function branding(): JsonResponse
    {
        $company = $this->settings->company();

        return $this->success([
            'clinic_name' => $company['business_name'],
            'tagline' => 'Atención más ordenada: pacientes, agenda, consultas y recetas en un solo lugar.',
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $remember = (bool) ($payload['remember'] ?? false);

        $user = $this->authService->login($payload);

        // Sesión más larga en este equipo cuando marcan "Recordarme".
        if ($remember) {
            config(['session.lifetime' => max((int) config('session.lifetime'), 60 * 24 * 30)]);
        }

        // Prevent session fixation after a privilege change.
        // Session is available because Sanctum marked this as a stateful
        // frontend request (Origin/Referer ∈ SANCTUM_STATEFUL_DOMAINS).
        $request->session()->regenerate();

        $user->load('roles');

        return $this->success(
            (new UserResource($user))->withPermissions(),
            'Sesión iniciada correctamente.'
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles');

        return $this->success((new UserResource($user))->withPermissions());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Sesión cerrada correctamente.');
    }
}
