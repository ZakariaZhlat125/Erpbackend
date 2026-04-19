<?php

use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('tasks', [TaskController::class, 'index'])
        ->middleware('permission:tasks:read');
    
    Route::get('tasks/{task}', [TaskController::class, 'show'])
        ->middleware('permission:tasks:read');
    
    Route::post('tasks', [TaskController::class, 'store'])
        ->middleware('permission:tasks:write');
    
    Route::put('tasks/{task}', [TaskController::class, 'update'])
        ->middleware('permission:tasks:write');
    
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])
        ->middleware('permission:tasks:delete');
});
