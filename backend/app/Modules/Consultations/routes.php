<?php

use App\Modules\Consultations\Controllers\ConsultationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('consultations/{id}/finalize', [ConsultationController::class, 'finalize']);
    Route::post('consultations/{id}/vitals', [ConsultationController::class, 'storeVitals']);
    Route::apiResource('consultations', ConsultationController::class)
        ->except(['destroy'])
        ->parameters(['consultations' => 'id']);
});
