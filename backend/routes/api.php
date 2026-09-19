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
    Route::get('/health', function () {
        $database = false;
        $databaseError = null;
        $sessionsTable = false;
        $usersCount = null;

        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            $database = true;
            $sessionsTable = \Illuminate\Support\Facades\Schema::hasTable('sessions');
            if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                $usersCount = \Illuminate\Support\Facades\DB::table('users')->count();
            }
        } catch (\Throwable $e) {
            $databaseError = class_basename($e).': '.$e->getMessage();
        }

        $ok = $database && $sessionsTable;

        return response()->json([
            'message' => $ok ? 'OK' : 'DEGRADED',
            'data' => [
                'app' => config('app.name'),
                'version' => '1.0.0',
                'time' => now()->toIso8601String(),
                'database' => $database,
                'sessions_table' => $sessionsTable,
                'users_count' => $usersCount,
                'session_driver' => config('session.driver'),
                'database_error' => $databaseError,
            ],
        ], $ok ? 200 : 503);
    });

    foreach (glob(app_path('Modules/*/routes.php')) as $moduleRoutes) {
        require $moduleRoutes;
    }
});
