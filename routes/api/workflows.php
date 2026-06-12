<?php

use App\Http\Controllers\Api\WorkflowController;
use App\Http\Controllers\Api\WorkflowApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('workflows')->name('api.workflows.')->group(function () {
    
    // ==================== Workflow Management ====================
    Route::middleware('permission:workflows:read')->group(function () {
        Route::get('/', [WorkflowController::class, 'index'])->name('index');
        Route::get('/statistics', [WorkflowController::class, 'statistics'])->name('statistics');
        Route::get('/entity-types', [WorkflowController::class, 'entityTypes'])->name('entity-types');
        Route::get('/instances', [WorkflowController::class, 'instances'])->name('instances');
        Route::get('/instances/{id}', [WorkflowController::class, 'showInstance'])->name('instances.show');
        Route::get('/{id}', [WorkflowController::class, 'show'])->name('show');
    });

    Route::middleware('permission:workflows:write')->group(function () {
        Route::post('/', [WorkflowController::class, 'store'])->name('store');
        Route::put('/{id}', [WorkflowController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-active', [WorkflowController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/instances/{id}/cancel', [WorkflowController::class, 'cancelInstance'])->name('instances.cancel');
    });

    Route::middleware('permission:workflows:delete')->group(function () {
        Route::delete('/{id}', [WorkflowController::class, 'destroy'])->name('destroy');
    });

    // ==================== Approvals ====================
    Route::prefix('approvals')->name('approvals.')->group(function () {
        Route::get('/pending', [WorkflowApprovalController::class, 'pending'])->name('pending');
        Route::get('/history', [WorkflowApprovalController::class, 'history'])->name('history');
        Route::get('/count', [WorkflowApprovalController::class, 'count'])->name('count');
        Route::get('/{id}', [WorkflowApprovalController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [WorkflowApprovalController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [WorkflowApprovalController::class, 'reject'])->name('reject');
        Route::post('/{id}/delegate', [WorkflowApprovalController::class, 'delegate'])->name('delegate');
    });
});
