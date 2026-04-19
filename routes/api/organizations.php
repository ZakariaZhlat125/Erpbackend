<?php

use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('organizations', [OrganizationController::class, 'index'])
        ->middleware('permission:settings:read');
    
    Route::get('organizations/{organization}', [OrganizationController::class, 'show'])
        ->middleware('permission:settings:read');
    
    Route::post('organizations', [OrganizationController::class, 'store'])
        ->middleware('permission:settings:write');
    
    Route::put('organizations/{organization}', [OrganizationController::class, 'update'])
        ->middleware('permission:settings:write');
    
    Route::delete('organizations/{organization}', [OrganizationController::class, 'destroy'])
        ->middleware('permission:settings:write');
});
