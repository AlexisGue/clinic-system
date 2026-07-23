<?php

use App\Modules\Audit\Controllers\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('audits', [AuditController::class, 'index'])->name('audits.index');
});
