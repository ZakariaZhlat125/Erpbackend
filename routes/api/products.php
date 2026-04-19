<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('products', [ProductController::class, 'index'])
        ->middleware('permission:products:read');
    
    Route::get('products/{product}', [ProductController::class, 'show'])
        ->middleware('permission:products:read');
    
    Route::post('products', [ProductController::class, 'store'])
        ->middleware('permission:products:write');
    
    Route::put('products/{product}', [ProductController::class, 'update'])
        ->middleware('permission:products:write');
    
    Route::delete('products/{product}', [ProductController::class, 'destroy'])
        ->middleware('permission:products:delete');
});
