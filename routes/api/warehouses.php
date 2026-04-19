<?php

use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('warehouses', [WarehouseController::class, 'index'])
        ->middleware('permission:warehouses:read');
    
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show'])
        ->middleware('permission:warehouses:read');
    
    Route::post('warehouses', [WarehouseController::class, 'store'])
        ->middleware('permission:warehouses:write');
    
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update'])
        ->middleware('permission:warehouses:write');
    
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'destroy'])
        ->middleware('permission:warehouses:write');
});
