<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/api/organizations.php';

require __DIR__.'/api/branches.php';

require __DIR__.'/api/parties.php';

require __DIR__.'/api/invoices.php';

require __DIR__.'/api/accounts.php';

require __DIR__.'/api/payments.php';

require __DIR__.'/api/products.php';

require __DIR__.'/api/warehouses.php';

require __DIR__.'/api/employees.php';

require __DIR__.'/api/projects.php';

require __DIR__.'/api/tasks.php';
