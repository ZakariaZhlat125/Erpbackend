<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('inventory')->name('api.inventory.')->group(function () {
    
    // Products
    Route::get('products/statistics', [ProductController::class, 'statistics'])
        ->name('products.statistics')
        ->middleware('permission:products:read');
    
    Route::get('products/search', [ProductController::class, 'search'])
        ->name('products.search')
        ->middleware('permission:products:read');
    
    Route::get('products/low-stock', [ProductController::class, 'lowStock'])
        ->name('products.low-stock')
        ->middleware('permission:products:read');
    
    Route::get('products/export', [ProductController::class, 'export'])
        ->name('products.export')
        ->middleware('permission:products:read');
    
    Route::post('products/bulk-update-prices', [ProductController::class, 'bulkUpdatePrices'])
        ->name('products.bulk-update-prices')
        ->middleware('permission:products:write');
    
    Route::post('products/bulk-activate', [ProductController::class, 'bulkActivate'])
        ->name('products.bulk-activate')
        ->middleware('permission:products:write');
    
    Route::post('products/import', [ProductController::class, 'import'])
        ->name('products.import')
        ->middleware('permission:products:write');
    
    Route::apiResource('products', ProductController::class)
        ->middleware('permission:products:read');
    
    // Product Categories
    Route::prefix('product-categories')->name('product-categories.')->group(function () {
        // Implement ProductCategoryController endpoints
        // Route::apiResource('/', ProductCategoryController::class);
    });
    
    // Units of Measurement
    Route::prefix('units')->name('units.')->group(function () {
        // Implement UnitController endpoints
        // Route::apiResource('/', UnitController::class);
    });
    
    // Warehouses
    Route::apiResource('warehouses', WarehouseController::class)
        ->middleware('permission:warehouses:read');
    
    // Stock Balances
    Route::prefix('stock-balances')->name('stock-balances.')->group(function () {
        // Implement StockBalanceController endpoints
        // Route::get('/', [StockBalanceController::class, 'index']);
    });
    
    // Stock Movements
    Route::prefix('stock-movements')->name('stock-movements.')->group(function () {
        // Implement StockMovementController endpoints
        // Route::apiResource('/', StockMovementController::class);
    });
    
    // Stock Transfers
    Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
        // Implement StockTransferController endpoints
        // Route::post('/', [StockTransferController::class, 'store']);
    });
    
    // Stock Counts (Physical Inventory)
    Route::prefix('stock-counts')->name('stock-counts.')->group(function () {
        // Implement StockCountController endpoints
        // Route::apiResource('/', StockCountController::class);
        // Route::post('{stockCount}/approve', [StockCountController::class, 'approve']);
    });
});
