<?php

use App\Http\Middleware\SecurityHeaders;
use App\Support\Exceptions\DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA mode: requests from SANCTUM_STATEFUL_DOMAINS are
        // authenticated via secure session cookies (HttpOnly + CSRF).
        $middleware->statefulApi();

        $middleware->api(append: [
            SecurityHeaders::class,
        ]);

        $middleware->throttleApi();

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        $json = fn (string $message, int $status, string $code, array $errors = []) => response()->json([
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
        ], $status);

        $exceptions->render(function (ValidationException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json($e->getMessage(), 422, 'VALIDATION_ERROR', $e->errors());
            }
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json('No autenticado.', 401, 'UNAUTHENTICATED');
            }
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json('No tienes permiso para realizar esta acción.', 403, 'FORBIDDEN');
            }
        });

        // Uniform 404: never reveal whether a resource exists internally.
        $exceptions->render(function (ModelNotFoundException|NotFoundHttpException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json('Recurso no encontrado.', 404, 'NOT_FOUND');
            }
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json('Demasiadas peticiones. Intenta de nuevo en unos momentos.', 429, 'TOO_MANY_REQUESTS');
            }
        });

        $exceptions->render(function (DomainException $e, Request $request) use ($json) {
            if ($request->is('api/*')) {
                return $json($e->getMessage(), $e->getStatus(), $e->getErrorCode());
            }
        });
    })->create();
