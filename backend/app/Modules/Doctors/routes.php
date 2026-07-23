<?php

use App\Modules\Doctors\Controllers\DoctorController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('doctors/options', [DoctorController::class, 'options']);
    Route::put('doctors/{id}/specialties', [DoctorController::class, 'syncSpecialties']);
    Route::get('doctors/{id}/schedules', [DoctorController::class, 'schedules']);
    Route::put('doctors/{id}/schedules', [DoctorController::class, 'updateSchedules']);
    Route::apiResource('doctors', DoctorController::class)
        ->parameters(['doctors' => 'id']);
});
