<?php

use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\EmployeeSelfServiceController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\PayrollController;
use App\Http\Controllers\Api\LoanController;
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
    Route::prefix('attendance')->name('attendance.')->middleware('permission:attendance:read')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('/report', [AttendanceController::class, 'report'])->name('report');
        Route::get('/{id}', [AttendanceController::class, 'show'])->name('show');
        
        Route::middleware('permission:attendance:write')->group(function () {
            Route::post('/', [AttendanceController::class, 'store'])->name('store');
            Route::put('/{id}', [AttendanceController::class, 'update'])->name('update');
            Route::post('/mark-absent', [AttendanceController::class, 'markAbsent'])->name('mark-absent');
        });
    });
    
    // Leave Requests
    Route::prefix('leaves')->name('leaves.')->group(function () {
        // Implement LeaveRequestController endpoints
        // Route::apiResource('/', LeaveRequestController::class);
        // Route::post('{leave}/approve', [LeaveRequestController::class, 'approve']);
        // Route::post('{leave}/reject', [LeaveRequestController::class, 'reject']);
    });
    
    // Payroll
    Route::prefix('payroll')->name('payroll.')->middleware('permission:payroll:read')->group(function () {
        Route::get('/runs', [PayrollController::class, 'runs'])->name('runs');
        Route::get('/runs/{id}', [PayrollController::class, 'showRun'])->name('runs.show');
        Route::get('/payslips/{id}', [PayrollController::class, 'showPayslip'])->name('payslips.show');
        Route::get('/salary-components', [PayrollController::class, 'components'])->name('components');
        
        Route::middleware('permission:payroll:write')->group(function () {
            Route::post('/runs', [PayrollController::class, 'createRun'])->name('runs.create');
            Route::post('/runs/{id}/calculate', [PayrollController::class, 'calculate'])->name('runs.calculate');
            Route::post('/salary-components', [PayrollController::class, 'storeComponent'])->name('components.store');
            Route::post('/seed-components', [PayrollController::class, 'seedComponents'])->name('seed-components');
        });
        
        Route::middleware('permission:payroll:approve')->group(function () {
            Route::post('/runs/{id}/approve', [PayrollController::class, 'approve'])->name('runs.approve');
            Route::post('/runs/{id}/pay', [PayrollController::class, 'pay'])->name('runs.pay');
        });
    });

    // Loans
    Route::prefix('loans')->name('loans.')->middleware('permission:payroll:read')->group(function () {
        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('/{id}', [LoanController::class, 'show'])->name('show');
        
        Route::middleware('permission:payroll:write')->group(function () {
            Route::post('/', [LoanController::class, 'store'])->name('store');
            Route::put('/{id}', [LoanController::class, 'update'])->name('update');
        });
        
        Route::post('/{id}/approve', [LoanController::class, 'approve'])
            ->name('approve')
            ->middleware('permission:payroll:approve');
    });

    // ==================== Employee Self Service ====================
    Route::prefix('self-service')->name('self-service.')->group(function () {
        Route::get('/dashboard', [EmployeeSelfServiceController::class, 'dashboard'])->name('dashboard');
        
        // Attendance
        Route::post('/check-in', [EmployeeSelfServiceController::class, 'checkIn'])->name('check-in');
        Route::post('/check-out', [EmployeeSelfServiceController::class, 'checkOut'])->name('check-out');
        Route::get('/attendance', [EmployeeSelfServiceController::class, 'attendanceHistory'])->name('attendance');
        
        // Leaves
        Route::get('/leave-balance', [EmployeeSelfServiceController::class, 'leaveBalance'])->name('leave-balance');
        Route::get('/leaves', [EmployeeSelfServiceController::class, 'myLeaveRequests'])->name('leaves');
        Route::post('/leaves', [EmployeeSelfServiceController::class, 'requestLeave'])->name('leaves.request');
        
        // Payslips
        Route::get('/payslips', [EmployeeSelfServiceController::class, 'myPayslips'])->name('payslips');
        Route::get('/payslips/{id}', [EmployeeSelfServiceController::class, 'viewPayslip'])->name('payslips.show');
        
        // Loans
        Route::get('/loans', [EmployeeSelfServiceController::class, 'myLoans'])->name('loans');
        Route::post('/loans', [EmployeeSelfServiceController::class, 'requestLoan'])->name('loans.request');
        
        // Profile
        Route::get('/profile', [EmployeeSelfServiceController::class, 'myProfile'])->name('profile');
        Route::put('/profile', [EmployeeSelfServiceController::class, 'updateProfile'])->name('profile.update');
    });
});
