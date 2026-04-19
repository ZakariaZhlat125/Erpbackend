<?php

use App\Http\Controllers\Api\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('accounts', [AccountController::class, 'index'])
        ->middleware('permission:accounts:read');
    
    Route::get('accounts/{account}', [AccountController::class, 'show'])
        ->middleware('permission:accounts:read');
    
    Route::post('accounts', [AccountController::class, 'store'])
        ->middleware('permission:accounts:write');
    
    Route::put('accounts/{account}', [AccountController::class, 'update'])
        ->middleware('permission:accounts:write');
    
    Route::delete('accounts/{account}', [AccountController::class, 'destroy'])
        ->middleware('permission:accounts:delete');
});
