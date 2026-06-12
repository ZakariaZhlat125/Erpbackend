<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\JournalController;
use App\Http\Controllers\Api\CostCenterController;
use App\Http\Controllers\Api\TaxRateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('accounting')->name('api.accounting.')->group(function () {
    
    // Chart of Accounts
    Route::apiResource('accounts', AccountController::class)
        ->middleware('permission:accounts:read');
    
    Route::get('accounts/{account}/statement', [AccountController::class, 'statement'])
        ->name('accounts.statement')
        ->middleware('permission:accounts:read');
    
    // Tax Rates
    Route::prefix('tax-rates')->name('tax-rates.')->group(function () {
        Route::get('/for-sales', [TaxRateController::class, 'forSales'])->name('for-sales');
        Route::get('/for-purchase', [TaxRateController::class, 'forPurchase'])->name('for-purchase');
        Route::post('/calculate', [TaxRateController::class, 'calculate'])->name('calculate');
        Route::post('/seed-defaults', [TaxRateController::class, 'seedDefaults'])->name('seed-defaults');
        Route::get('/', [TaxRateController::class, 'index'])->name('index');
        Route::post('/', [TaxRateController::class, 'store'])->name('store');
        Route::get('/{id}', [TaxRateController::class, 'show'])->name('show');
        Route::put('/{id}', [TaxRateController::class, 'update'])->name('update');
        Route::delete('/{id}', [TaxRateController::class, 'destroy'])->name('destroy');
    });

    // Cost Centers
    Route::prefix('cost-centers')->name('cost-centers.')->group(function () {
        Route::get('/tree', [CostCenterController::class, 'tree'])->name('tree');
        Route::get('/{id}/statistics', [CostCenterController::class, 'statistics'])->name('statistics');
        Route::get('/', [CostCenterController::class, 'index'])->name('index');
        Route::post('/', [CostCenterController::class, 'store'])->name('store');
        Route::get('/{id}', [CostCenterController::class, 'show'])->name('show');
        Route::put('/{id}', [CostCenterController::class, 'update'])->name('update');
        Route::delete('/{id}', [CostCenterController::class, 'destroy'])->name('destroy');
    });
    
    // Invoices
    Route::get('invoices/statistics', [InvoiceController::class, 'statistics'])
        ->name('invoices.statistics')
        ->middleware('permission:invoices:read');
    
    Route::get('invoices/search', [InvoiceController::class, 'search'])
        ->name('invoices.search')
        ->middleware('permission:invoices:read');
    
    Route::get('invoices/export', [InvoiceController::class, 'export'])
        ->name('invoices.export')
        ->middleware('permission:invoices:read');
    
    Route::post('invoices/bulk-approve', [InvoiceController::class, 'bulkApprove'])
        ->name('invoices.bulk-approve')
        ->middleware('permission:invoices:approve');
    
    Route::post('invoices/bulk-delete', [InvoiceController::class, 'bulkDelete'])
        ->name('invoices.bulk-delete')
        ->middleware('permission:invoices:write');
    
    Route::apiResource('invoices', InvoiceController::class)
        ->middleware('permission:invoices:read');
    
    Route::post('invoices/{invoice}/approve', [InvoiceController::class, 'approve'])
        ->name('invoices.approve')
        ->middleware('permission:invoices:approve');
    
    Route::post('invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
        ->name('invoices.cancel')
        ->middleware('permission:invoices:cancel');
    
    Route::post('invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])
        ->name('invoices.duplicate')
        ->middleware('permission:invoices:write');
    
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->name('invoices.pdf')
        ->middleware('permission:invoices:read');
    
    // Payments
    Route::apiResource('payments', PaymentController::class)
        ->middleware('permission:payments:read');
    
    // Journal Batches & Lines
    Route::prefix('journals')->name('journals.')->middleware('permission:journals:read')->group(function () {
        Route::get('/trial-balance', [JournalController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/account-statement/{accountId}', [JournalController::class, 'accountStatement'])->name('account-statement');
        Route::get('/', [JournalController::class, 'index'])->name('index');
        Route::get('/{id}', [JournalController::class, 'show'])->name('show');
        
        Route::middleware('permission:journals:write')->group(function () {
            Route::post('/', [JournalController::class, 'store'])->name('store');
            Route::put('/{id}', [JournalController::class, 'update'])->name('update');
            Route::delete('/{id}', [JournalController::class, 'destroy'])->name('destroy');
        });
        
        Route::middleware('permission:journals:post')->group(function () {
            Route::post('/{id}/post', [JournalController::class, 'post'])->name('post');
            Route::post('/{id}/void', [JournalController::class, 'void'])->name('void');
            Route::post('/{id}/reverse', [JournalController::class, 'reverse'])->name('reverse');
        });
    });
    
    // Accounting Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        // Implement ReportController endpoints
        // Route::get('trial-balance', [ReportController::class, 'trialBalance']);
        // Route::get('income-statement', [ReportController::class, 'incomeStatement']);
        // Route::get('balance-sheet', [ReportController::class, 'balanceSheet']);
        // Route::get('cash-flows', [ReportController::class, 'cashFlows']);
        // Route::get('ledger', [ReportController::class, 'ledger']);
        // Route::get('receivables', [ReportController::class, 'receivables']);
        // Route::get('payables', [ReportController::class, 'payables']);
        // Route::get('tax-return', [ReportController::class, 'taxReturn']);
        // Route::get('daily', [ReportController::class, 'daily']);
        // Route::get('employee-salaries', [ReportController::class, 'employeeSalaries']);
        // Route::get('inventory-valuation', [ReportController::class, 'inventoryValuation']);
        // Route::get('commissions', [ReportController::class, 'commissions']);
    });
});
