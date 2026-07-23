<?php

use App\Modules\Prescriptions\Controllers\PrescriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('prescriptions/{id}/cancel', [PrescriptionController::class, 'cancel']);
    Route::get('prescriptions/{id}/pdf', [PrescriptionController::class, 'pdf']);
    Route::get('prescriptions', [PrescriptionController::class, 'index']);
    Route::post('prescriptions', [PrescriptionController::class, 'store']);
    Route::get('prescriptions/{id}', [PrescriptionController::class, 'show']);
});
