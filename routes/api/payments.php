<?php

use App\Http\Controllers\Api\PaymentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('payments', [PaymentController::class, 'index'])
        ->middleware('permission:payments:read');
    
    Route::get('payments/{payment}', [PaymentController::class, 'show'])
        ->middleware('permission:payments:read');
    
    Route::post('payments', [PaymentController::class, 'store'])
        ->middleware('permission:payments:write');
    
    Route::put('payments/{payment}', [PaymentController::class, 'update'])
        ->middleware('permission:payments:write');
    
    Route::delete('payments/{payment}', [PaymentController::class, 'destroy'])
        ->middleware('permission:payments:write');
});
