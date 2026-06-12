<?php

use App\Http\Controllers\Api\TimelineController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('timeline')->name('api.timeline.')->group(function () {
    
    // Mentions - Search users for @mention
    Route::get('/mentions/search', [TimelineController::class, 'searchUsers'])->name('mentions.search');
    Route::get('/mentions/recent', [TimelineController::class, 'recentMentions'])->name('mentions.recent');

    // Timeline for any entity
    // GET /api/v1/timeline/{entityType}/{entityId}
    // entityType: invoice, payment, party, product, employee, project, task
    Route::get('/{entityType}/{entityId}', [TimelineController::class, 'timeline'])
        ->name('show')
        ->where('entityType', 'invoice|payment|party|product|employee|project|task');

    // Comments
    Route::prefix('/{entityType}/{entityId}/comments')->name('comments.')->group(function () {
        Route::get('/', [TimelineController::class, 'comments'])->name('index');
        Route::post('/', [TimelineController::class, 'storeComment'])->name('store');
    })->where('entityType', 'invoice|payment|party|product|employee|project|task');

    Route::prefix('/comments')->name('comments.')->group(function () {
        Route::put('/{commentId}', [TimelineController::class, 'updateComment'])->name('update');
        Route::delete('/{commentId}', [TimelineController::class, 'deleteComment'])->name('destroy');
        Route::post('/{commentId}/toggle-pin', [TimelineController::class, 'togglePinComment'])->name('toggle-pin');
    });

    // Attachments
    Route::prefix('/{entityType}/{entityId}/attachments')->name('attachments.')->group(function () {
        Route::get('/', [TimelineController::class, 'attachments'])->name('index');
        Route::post('/', [TimelineController::class, 'storeAttachment'])->name('store');
    })->where('entityType', 'invoice|payment|party|product|employee|project|task');

    Route::prefix('/attachments')->name('attachments.')->group(function () {
        Route::delete('/{attachmentId}', [TimelineController::class, 'deleteAttachment'])->name('destroy');
        Route::get('/{attachmentId}/download', [TimelineController::class, 'downloadAttachment'])->name('download');
    });
});
