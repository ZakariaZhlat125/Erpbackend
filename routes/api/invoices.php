<?php

use App\Http\Controllers\Api\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('invoices', [InvoiceController::class, 'index'])
        ->middleware('permission:invoices:read');
    
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])
        ->middleware('permission:invoices:read');
    
    Route::post('invoices', [InvoiceController::class, 'store'])
        ->middleware('permission:invoices:write');
    
    Route::put('invoices/{invoice}', [InvoiceController::class, 'update'])
        ->middleware('permission:invoices:write');
    
    Route::delete('invoices/{invoice}', [InvoiceController::class, 'destroy'])
        ->middleware('permission:invoices:delete');
    
    Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])
        ->middleware('permission:invoices:approve');
    
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
        ->middleware('permission:invoices:cancel');
});
