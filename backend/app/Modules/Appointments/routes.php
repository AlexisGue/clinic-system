<?php

use App\Modules\Appointments\Controllers\AppointmentController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('appointments/calendar', [AppointmentController::class, 'calendar']);
    Route::post('appointments/{id}/reschedule', [AppointmentController::class, 'reschedule']);
    Route::post('appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
    Route::post('appointments/{id}/status', [AppointmentController::class, 'updateStatus']);
    Route::get('appointments', [AppointmentController::class, 'index']);
    Route::post('appointments', [AppointmentController::class, 'store']);
    Route::get('appointments/{id}', [AppointmentController::class, 'show']);
});
