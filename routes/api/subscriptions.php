<?php

use App\Http\Controllers\Api\Admin\SubscriptionController as AdminSubscriptionController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('my-subscription', [SubscriptionController::class, 'mySubscription']);
    Route::get('my-subscription-history', [SubscriptionController::class, 'myHistory']);
    Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
    Route::post('subscriptions/{id}/unsubscribe', [SubscriptionController::class, 'unsubscribe']);
    Route::post('subscriptions/{id}/renew', [SubscriptionController::class, 'renew']);
});

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::post('subscriptions/subscribe-user', [AdminSubscriptionController::class, 'subscribeUser']);
    Route::patch('subscriptions/{id}/change-status', [AdminSubscriptionController::class, 'changeStatus']);
    Route::post('subscriptions/{id}/renew', [AdminSubscriptionController::class, 'renewSubscription']);
    Route::post('subscriptions/{id}/cancel', [AdminSubscriptionController::class, 'cancelSubscription']);
    Route::apiResource('subscriptions', AdminSubscriptionController::class);
});
