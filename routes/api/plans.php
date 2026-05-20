<?php

use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\PlanController as OrganizationPlanController;
use Illuminate\Support\Facades\Route;

/**
 * Organization Routes - View available plans only (read-only)
 * Accessible by: OWNER_ORGANIZATION, Manager, User (any authenticated user)
 */
Route::middleware('auth:sanctum')->prefix('organization')->group(function () {
    Route::get('available-plans', [OrganizationPlanController::class, 'availablePlans']);
    Route::get('plans/{id}', [OrganizationPlanController::class, 'show']);
});

/**
 * Admin Routes - Full CRUD access
 * Accessible by: Super Admin, Admin (with plans permissions)
 */
Route::middleware(['auth:sanctum', 'permission:plans:read|plans:write|plans:delete'])->prefix('admin')->group(function () {
    Route::patch('plans/{id}/change-status', [PlanController::class, 'changeStatus'])
        ->middleware('permission:plans:write');
    Route::patch('plans/{id}/toggle-popular', [PlanController::class, 'togglePopular'])
        ->middleware('permission:plans:write');
    Route::apiResource('plans', PlanController::class);
});
