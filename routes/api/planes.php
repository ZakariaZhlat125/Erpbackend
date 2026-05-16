<?php

use App\Http\Controllers\Api\PlaneController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('planes', PlaneController::class);
});
