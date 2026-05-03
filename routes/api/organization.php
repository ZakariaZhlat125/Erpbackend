<?php

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    
    // Organizations
    Route::apiResource('organizations', OrganizationController::class)
        ->middleware('permission:organizations:read');
    
    // Organizations bulk create
    Route::post('organizations/bulk', [OrganizationController::class, 'bulkStore'])
        ->name('organizations.bulk')
        ->middleware('permission:organizations:write');

    // Organizations toggle status
    Route::post('organizations/{id}/toggle-status', [OrganizationController::class, 'toggleStatus'])
        ->name('organizations.toggle-status')
        ->middleware('permission:organizations:write');
    
    // Branches (nested under organizations)
    Route::prefix('organizations/{organization}/branches')->name('branches.')->group(function () {
        Route::get('/', [BranchController::class, 'index'])
            ->name('index')
            ->middleware('permission:branches:read');

        Route::post('/', [BranchController::class, 'store'])
            ->name('store')
            ->middleware('permission:branches:write');

        Route::get('{branch}', [BranchController::class, 'show'])
            ->name('show')
            ->middleware('permission:branches:read');

        Route::put('{branch}', [BranchController::class, 'update'])
            ->name('update')
            ->middleware('permission:branches:write');

        Route::delete('{branch}', [BranchController::class, 'destroy'])
            ->name('destroy')
            ->middleware('permission:branches:write');

        Route::post('{branch}/toggle-status', [BranchController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('permission:branches:write');
    });
    
    // Parties (Customers, Suppliers, Agents, Contractors)
    Route::prefix('parties')->name('parties.')->group(function () {
        Route::apiResource('/', PartyController::class, ['as' => 'parties'])
            ->parameters(['' => 'party'])
            ->middleware('permission:parties:read');
        
        // Add contact to party
        Route::post('{party}/contacts', [PartyController::class, 'addContact'])
            ->name('contacts.add')
            ->middleware('permission:parties:write');
        
        // Add role to party
        Route::post('{party}/roles', [PartyController::class, 'addRole'])
            ->name('roles.add')
            ->middleware('permission:parties:write');
        
        // Get party statement (invoices + payments)
        Route::get('{party}/statement', [PartyController::class, 'statement'])
            ->name('statement')
            ->middleware('permission:parties:read');
        
        // Parties statistics
        Route::get('statistics', [PartyController::class, 'statistics'])
            ->name('statistics')
            ->middleware('permission:parties:read');
        
        // Parties search
        Route::get('search', [PartyController::class, 'search'])
            ->name('search')
            ->middleware('permission:parties:read');
        
        // Parties export
        Route::get('export', [PartyController::class, 'export'])
            ->name('export')
            ->middleware('permission:parties:read');
        
        // Parties bulk activate
        Route::post('bulk-activate', [PartyController::class, 'bulkActivate'])
            ->name('bulk-activate')
            ->middleware('permission:parties:write');

        // Party toggle status
        Route::post('{party}/toggle-status', [PartyController::class, 'toggleStatus'])
            ->name('toggle-status')
            ->middleware('permission:parties:write');
    });
});
