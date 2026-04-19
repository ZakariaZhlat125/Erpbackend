<?php

use App\Http\Controllers\Api\ProjectController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('projects', [ProjectController::class, 'index'])
        ->middleware('permission:projects:read');
    
    Route::get('projects/{project}', [ProjectController::class, 'show'])
        ->middleware('permission:projects:read');
    
    Route::post('projects', [ProjectController::class, 'store'])
        ->middleware('permission:projects:write');
    
    Route::put('projects/{project}', [ProjectController::class, 'update'])
        ->middleware('permission:projects:write');
    
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])
        ->middleware('permission:projects:delete');
});
