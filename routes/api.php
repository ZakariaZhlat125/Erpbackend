<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Core Module: Auth, Users, Roles, Permissions, Settings
require __DIR__.'/api/core.php';

// Organization Module: Organizations, Branches, Parties
require __DIR__.'/api/organization.php';

// Accounting Module: Accounts, Invoices, Payments, Journal, Reports
require __DIR__.'/api/accounting.php';

// Inventory Module: Products, Warehouses, Stock, Movements
require __DIR__.'/api/inventory.php';

// HR Module: Employees, Attendance, Leaves, Payroll
require __DIR__.'/api/hr.php';

// Projects Module: Projects, Tasks, Time Entries
require __DIR__.'/api/projects.php';

require __DIR__.'/api/account_services.php';
