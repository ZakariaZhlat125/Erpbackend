<?php

use App\Http\Controllers\Api\AccountServiceController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('account_services', AccountServiceController::class);
});
