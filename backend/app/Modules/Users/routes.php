<?php

use App\Modules\Users\Controllers\RoleController;
use App\Modules\Users\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('roles/options', [RoleController::class, 'options'])->name('roles.options');
    Route::get('permissions', [RoleController::class, 'permissions'])->name('permissions.index');

    Route::apiResource('users', UserController::class);
    Route::apiResource('roles', RoleController::class);
});
