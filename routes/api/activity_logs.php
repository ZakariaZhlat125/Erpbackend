<?php

use App\Http\Controllers\Api\ActivityLogController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('activity_logs', ActivityLogController::class);
});
