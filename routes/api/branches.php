<?php

use App\Http\Controllers\Api\BranchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('branches', [BranchController::class, 'index'])
        ->middleware('permission:settings:read');
    
    Route::get('branches/{branch}', [BranchController::class, 'show'])
        ->middleware('permission:settings:read');
    
    Route::post('branches', [BranchController::class, 'store'])
        ->middleware('permission:settings:write');
    
    Route::put('branches/{branch}', [BranchController::class, 'update'])
        ->middleware('permission:settings:write');
    
    Route::delete('branches/{branch}', [BranchController::class, 'destroy'])
        ->middleware('permission:settings:write');
});
