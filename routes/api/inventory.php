<?php

use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\InventoryController;
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
    Route::prefix('stock-transfers')->name('stock-transfers.')->middleware('permission:stock:read')->group(function () {
        Route::get('/', [StockTransferController::class, 'index'])->name('index');
        Route::get('/{id}', [StockTransferController::class, 'show'])->name('show');
        
        Route::middleware('permission:stock:adjust')->group(function () {
            Route::post('/', [StockTransferController::class, 'store'])->name('store');
            Route::put('/{id}', [StockTransferController::class, 'update'])->name('update');
            Route::post('/{id}/submit', [StockTransferController::class, 'submit'])->name('submit');
            Route::post('/{id}/ship', [StockTransferController::class, 'ship'])->name('ship');
            Route::post('/{id}/receive', [StockTransferController::class, 'receive'])->name('receive');
            Route::post('/{id}/cancel', [StockTransferController::class, 'cancel'])->name('cancel');
        });
        
        Route::post('/{id}/approve', [StockTransferController::class, 'approve'])
            ->name('approve')
            ->middleware('permission:stock:approve_count');
    });

    // Serial Numbers & Batches
    Route::prefix('serials')->name('serials.')->middleware('permission:stock:read')->group(function () {
        Route::get('/', [InventoryController::class, 'serials'])->name('index');
        Route::get('/available', [InventoryController::class, 'availableSerials'])->name('available');
        Route::get('/{serial}', [InventoryController::class, 'serialDetails'])->name('show');
    });

    Route::prefix('batches')->name('batches.')->middleware('permission:stock:read')->group(function () {
        Route::get('/', [InventoryController::class, 'batches'])->name('index');
        Route::get('/expiring', [InventoryController::class, 'expiringBatches'])->name('expiring');
        Route::get('/available', [InventoryController::class, 'availableBatches'])->name('available');
        Route::get('/{batch}', [InventoryController::class, 'batchDetails'])->name('show');
    });
    
    // Stock Counts (Physical Inventory)
    Route::prefix('stock-counts')->name('stock-counts.')->group(function () {
        // Implement StockCountController endpoints
        // Route::apiResource('/', StockCountController::class);
        // Route::post('{stockCount}/approve', [StockCountController::class, 'approve']);
    });
});
