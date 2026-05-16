<?php

use App\Http\Controllers\Api\Admin\PlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::patch('plans/{id}/change-status', [PlanController::class, 'changeStatus']);
    Route::apiResource('plans', PlanController::class);
});
