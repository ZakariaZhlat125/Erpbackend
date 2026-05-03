<?php

use App\Http\Controllers\Api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('hr')->name('api.hr.')->group(function () {
    
    // Employees
    Route::get('employees/statistics', [EmployeeController::class, 'statistics'])
        ->name('employees.statistics')
        ->middleware('permission:employees:read');
    
    Route::get('employees/search', [EmployeeController::class, 'search'])
        ->name('employees.search')
        ->middleware('permission:employees:read');
    
    Route::get('employees/org-chart', [EmployeeController::class, 'orgChart'])
        ->name('employees.org-chart')
        ->middleware('permission:employees:read');
    
    Route::get('employees/export', [EmployeeController::class, 'export'])
        ->name('employees.export')
        ->middleware('permission:employees:read');
    
    Route::post('employees/bulk-update-status', [EmployeeController::class, 'bulkUpdateStatus'])
        ->name('employees.bulk-update-status')
        ->middleware('permission:employees:write');
    
    Route::post('employees/import', [EmployeeController::class, 'import'])
        ->name('employees.import')
        ->middleware('permission:employees:write');
    
    Route::apiResource('employees', EmployeeController::class)
        ->middleware('permission:employees:read');
    
    // Attendance
    Route::prefix('attendance')->name('attendance.')->group(function () {
        // Implement AttendanceController endpoints
        // Route::apiResource('/', AttendanceController::class);
        // Route::post('import', [AttendanceController::class, 'import']);
        // Route::get('report', [AttendanceController::class, 'report']);
    });
    
    // Leave Requests
    Route::prefix('leaves')->name('leaves.')->group(function () {
        // Implement LeaveRequestController endpoints
        // Route::apiResource('/', LeaveRequestController::class);
        // Route::post('{leave}/approve', [LeaveRequestController::class, 'approve']);
        // Route::post('{leave}/reject', [LeaveRequestController::class, 'reject']);
    });
    
    // Payroll
    Route::prefix('payroll-runs')->name('payroll-runs.')->group(function () {
        // Implement PayrollRunController endpoints
        // Route::apiResource('/', PayrollRunController::class);
        // Route::put('{payrollRun}/lines/{line}', [PayrollRunController::class, 'updateLine']);
        // Route::post('{payrollRun}/approve', [PayrollRunController::class, 'approve']);
    });
});
