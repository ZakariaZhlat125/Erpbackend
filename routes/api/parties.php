<?php

use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('parties', [PartyController::class, 'index'])
        ->middleware('permission:parties:read');
    
    Route::get('parties/{party}', [PartyController::class, 'show'])
        ->middleware('permission:parties:read');
    
    Route::post('parties', [PartyController::class, 'store'])
        ->middleware('permission:parties:write');
    
    Route::put('parties/{party}', [PartyController::class, 'update'])
        ->middleware('permission:parties:write');
    
    Route::delete('parties/{party}', [PartyController::class, 'destroy'])
        ->middleware('permission:parties:delete');
});
