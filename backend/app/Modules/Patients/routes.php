<?php

use App\Modules\Patients\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('patients/options', [PatientController::class, 'options']);
    Route::get('patients/{id}/history', [PatientController::class, 'history']);
    Route::get('patients/{id}/contacts', [PatientController::class, 'contacts']);
    Route::post('patients/{id}/contacts', [PatientController::class, 'storeContact']);
    Route::put('patients/{id}/contacts/{contact}', [PatientController::class, 'updateContact']);
    Route::delete('patients/{id}/contacts/{contact}', [PatientController::class, 'destroyContact']);
    Route::apiResource('patients', PatientController::class)
        ->parameters(['patients' => 'id']);
});
