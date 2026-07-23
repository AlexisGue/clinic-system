<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Attempt to authenticate against the session (web) guard.
     *
     * Both "wrong password" and "unknown email" return the exact same
     * message, so an attacker cannot enumerate registered emails.
     *
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     *
     * @throws ValidationException
     */
    public function login(array $credentials): User
    {
        $remember = (bool) ($credentials['remember'] ?? false);
        $attempt = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        if (! Auth::guard('web')->attempt($attempt, $remember)) {
            throw ValidationException::withMessages([
                'email' => ['Correo o contraseña incorrectos. Verifica e intenta de nuevo.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();

            throw ValidationException::withMessages([
                'email' => ['Tu cuenta está desactivada. Contacta al administrador.'],
            ]);
        }

        // saveQuietly: updating a login timestamp must not fire model
        // events nor touch updated_at semantics of a real change.
        $user->forceFill(['last_login_at' => now()])->saveQuietly();

        return $user;
    }
}
