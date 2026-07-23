<?php

use App\Modules\Catalogs\Controllers\MedicineController;
use App\Modules\Catalogs\Controllers\SpecialtyController;
use App\Modules\Payments\Controllers\PaymentMethodController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('specialties/options', [SpecialtyController::class, 'options']);
    Route::apiResource('specialties', SpecialtyController::class)
        ->parameters(['specialties' => 'id']);

    Route::get('medicines/options', [MedicineController::class, 'options']);
    Route::apiResource('medicines', MedicineController::class)
        ->parameters(['medicines' => 'id']);

    Route::get('payment-methods', [PaymentMethodController::class, 'index']);
});
