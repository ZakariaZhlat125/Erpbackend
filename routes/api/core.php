<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Authentication - Public routes
Route::prefix('auth')->name('api.auth.')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register-company', [AuthController::class, 'registerCompany']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('refresh', [AuthController::class, 'refresh']);
});

// Protected routes (require authentication)
Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    
    // Authentication - Protected routes
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
    
    // Users
    Route::prefix('users')->name('users.')->group(function () {
        // Implement UserController endpoints
        // Route::apiResource('/', UserController::class);
    });
    
    // Roles & Permissions
    Route::prefix('roles')->name('roles.')->group(function () {
        // Implement RoleController endpoints
        // Route::apiResource('/', RoleController::class);
        // Route::put('{role}/permissions', [RoleController::class, 'updatePermissions']);
    });
    
    // Currencies (Organization - read-only)
    Route::prefix('currencies')->name('currencies.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\CurrencyController::class, 'index'])->name('index');
        Route::get('/base', [App\Http\Controllers\Api\CurrencyController::class, 'getBase'])->name('base');
        Route::post('/convert', [App\Http\Controllers\Api\CurrencyController::class, 'convert'])->name('convert');
        Route::get('/{id}', [App\Http\Controllers\Api\CurrencyController::class, 'show'])->name('show');
    });

    // Currencies (Admin - full CRUD)
    Route::prefix('admin/currencies')->name('admin.currencies.')->group(function () {
        Route::get('/', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'index'])->name('index');
        Route::post('/', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'store'])->name('store');
        Route::get('/{id}', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'show'])->name('show');
        Route::put('/{id}', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'update'])->name('update');
        Route::delete('/{id}', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/set-base', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'setBase'])->name('set-base');
        Route::post('/{id}/update-rate', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'updateRate'])->name('update-rate');
        Route::post('/{id}/toggle-active', [App\Http\Controllers\Api\Admin\CurrencyController::class, 'toggleActive'])->name('toggle-active');
    });
    
    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        // General Settings
        // Route::get('general', [SettingController::class, 'general']);
        // Route::put('general', [SettingController::class, 'updateGeneral']);
        
        // Payment Terms
        // Route::apiResource('payment-terms', PaymentTermController::class);
        
        // Salary Components
        // Route::apiResource('salary-components', SalaryComponentController::class);
    });
    
    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        // Implement NotificationController endpoints
        // Route::get('/', [NotificationController::class, 'index']);
        // Route::put('{notification}/read', [NotificationController::class, 'markAsRead']);
        // Route::post('mark-all-read', [NotificationController::class, 'markAllAsRead']);
    });
    
    // Activity Logs (Audit)
    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        // Implement ActivityLogController endpoints
        // Route::get('/', [ActivityLogController::class, 'index']);
    });
});
