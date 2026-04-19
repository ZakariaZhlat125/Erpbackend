<?php

use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('employees', [EmployeeController::class, 'index'])
        ->middleware('permission:employees:read');
    
    Route::get('employees/{employee}', [EmployeeController::class, 'show'])
        ->middleware('permission:employees:read');
    
    Route::post('employees', [EmployeeController::class, 'store'])
        ->middleware('permission:employees:write');
    
    Route::put('employees/{employee}', [EmployeeController::class, 'update'])
        ->middleware('permission:employees:write');
    
    Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])
        ->middleware('permission:employees:delete');
});
