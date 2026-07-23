<?php

use App\Modules\Reports\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('reports/patients', [ReportController::class, 'patients']);
    Route::get('reports/appointments', [ReportController::class, 'appointments']);
    Route::get('reports/consultations', [ReportController::class, 'consultations']);
    Route::get('reports/doctors', [ReportController::class, 'doctors']);
    Route::get('reports/payments', [ReportController::class, 'payments']);
});
