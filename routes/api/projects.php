<?php

use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('projects')->name('api.projects.')->group(function () {
    
    // Projects
    Route::apiResource('/', ProjectController::class, ['as' => 'projects'])
        ->parameters(['' => 'project'])
        ->middleware('permission:projects:read');
    
    Route::get('statistics', [ProjectController::class, 'statistics'])
        ->name('statistics')
        ->middleware('permission:projects:read');
    
    Route::get('search', [ProjectController::class, 'search'])
        ->name('search')
        ->middleware('permission:projects:read');
    
    Route::get('export', [ProjectController::class, 'export'])
        ->name('export')
        ->middleware('permission:projects:read');
    
    Route::get('{project}/dashboard', [ProjectController::class, 'dashboard'])
        ->name('dashboard')
        ->middleware('permission:projects:read');
    
    Route::put('{project}/progress', [ProjectController::class, 'updateProgress'])
        ->name('update-progress')
        ->middleware('permission:projects:write');
    
    Route::post('{project}/members', [ProjectController::class, 'addMember'])
        ->name('members.add')
        ->middleware('permission:projects:write');
    
    Route::delete('{project}/members/{user}', [ProjectController::class, 'removeMember'])
        ->name('members.remove')
        ->middleware('permission:projects:write');
    
    // Tasks within a project
    Route::prefix('{project}/tasks')->name('tasks.')->group(function () {
        Route::apiResource('/', TaskController::class, ['as' => 'project'])
            ->parameters(['' => 'task'])
            ->middleware('permission:tasks:read');
        
        Route::put('{task}/status', [TaskController::class, 'updateStatus'])
            ->name('update-status')
            ->middleware('permission:tasks:write');
    });
    
    // Time Entries
    Route::prefix('time-entries')->name('time-entries.')->group(function () {
        // Implement TimeEntryController endpoints
        // Route::apiResource('/', TimeEntryController::class);
    });
});
