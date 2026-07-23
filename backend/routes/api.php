<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Every module registers its own routes in app/Modules/{Module}/routes.php.
| They are all grouped here under the /api/v1 prefix.
*/

Route::prefix('v1')->group(function (): void {
    Route::get('/health', fn () => response()->json([
        'message' => 'OK',
        'data' => [
            'app' => config('app.name'),
            'version' => '1.0.0',
            'phase' => 7,
            'time' => now()->toIso8601String(),
        ],
    ]));

    foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoutes) {
        require $moduleRoutes;
    }
});
