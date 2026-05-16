<?php

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\PartyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    
    // Organizations
    Route::prefix('organizations')->name('organizations.')->group(function () {
        Route::middleware('permission:organizations:read')->group(function () {
            Route::get('/', [OrganizationController::class, 'index'])->name('index');
            Route::get('{organization}', [OrganizationController::class, 'show'])->name('show');
        });

        Route::middleware('permission:organizations:write')->group(function () {
            Route::post('/', [OrganizationController::class, 'store'])->name('store');
            Route::put('{organization}', [OrganizationController::class, 'update'])->name('update');
            Route::delete('{organization}', [OrganizationController::class, 'destroy'])->name('destroy');
            Route::post('bulk', [OrganizationController::class, 'bulkStore'])->name('bulk');
            Route::post('{id}/toggle-status', [OrganizationController::class, 'toggleStatus'])->name('toggle-status');
        });
    });
    
    // Branches (nested under organizations)
    Route::prefix('organizations/{organization}/branches')->name('branches.')->group(function () {
        Route::middleware('permission:branches:read')->group(function () {
            Route::get('/', [BranchController::class, 'index'])->name('index');
            Route::get('{branch}', [BranchController::class, 'show'])->name('show');
        });

        Route::middleware('permission:branches:write')->group(function () {
            Route::post('/', [BranchController::class, 'store'])->name('store');
            Route::put('{branch}', [BranchController::class, 'update'])->name('update');
            Route::delete('{branch}', [BranchController::class, 'destroy'])->name('destroy');
            Route::post('{branch}/toggle-status', [BranchController::class, 'toggleStatus'])->name('toggle-status');
        });
    });

    // Parties (Customers, Suppliers, Agents, Contractors) - nested under organizations
    Route::prefix('organizations/{organization}/parties')->name('parties.')->group(function () {
        Route::middleware('permission:parties:read')->group(function () {
            Route::get('/', [PartyController::class, 'index'])->name('index');
            Route::get('{party}', [PartyController::class, 'show'])->name('show');
            Route::get('{party}/statement', [PartyController::class, 'statement'])->name('statement');
            Route::get('statistics', [PartyController::class, 'statistics'])->name('statistics');
            Route::get('search', [PartyController::class, 'search'])->name('search');
            Route::get('export', [PartyController::class, 'export'])->name('export');
        });

        Route::middleware('permission:parties:write')->group(function () {
            Route::post('/', [PartyController::class, 'store'])->name('store');
            Route::put('{party}', [PartyController::class, 'update'])->name('update');
            Route::delete('{party}', [PartyController::class, 'destroy'])->name('destroy');
            Route::post('{party}/contacts', [PartyController::class, 'addContact'])->name('contacts.add');
            Route::post('{party}/roles', [PartyController::class, 'addRole'])->name('roles.add');
            Route::post('bulk-activate', [PartyController::class, 'bulkActivate'])->name('bulk-activate');
            Route::post('{party}/toggle-status', [PartyController::class, 'toggleStatus'])->name('toggle-status');
        });
    });
});
