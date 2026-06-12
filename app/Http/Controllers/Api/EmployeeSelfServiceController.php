<?php

namespace App\Http\Controllers\Api;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeSelfServiceController extends BaseApiController
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    protected function getEmployee(): ?Employee
    {
        return Employee::where('user_id', Auth::id())->first();
    }

    // ==================== Dashboard ====================
    public function dashboard(): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $today = now()->toDateString();
        $todayAttendance = Attendance::forEmployee($employee->id)
            ->forDate($today)
            ->first();

        $monthlyReport = $this->attendanceService->getMonthlyReport(
            $employee->id,
            now()->year,
            now()->month
        );

        $pendingLeaves = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $activeLoans = EmployeeLoan::forEmployee($employee->id)
            ->active()
            ->sum('remaining_amount');

        return $this->successResponse([
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->full_name ?? $employee->user?->name,
                'position' => $employee->position,
                'department' => $employee->department,
            ],
            'today' => [
                'date' => $today,
                'checked_in' => $todayAttendance?->isCheckedIn() ?? false,
                'checked_out' => $todayAttendance?->isCheckedOut() ?? false,
                'check_in_time' => $todayAttendance?->check_in,
                'check_out_time' => $todayAttendance?->check_out,
                'status' => $todayAttendance?->status,
            ],
            'month_summary' => [
                'present_days' => $monthlyReport['present_days'],
                'absent_days' => $monthlyReport['absent_days'],
                'late_days' => $monthlyReport['late_days'],
                'worked_hours' => round($monthlyReport['total_worked_minutes'] / 60, 1),
                'overtime_hours' => round($monthlyReport['total_overtime_minutes'] / 60, 1),
            ],
            'pending_leaves' => $pendingLeaves,
            'active_loans' => $activeLoans,
        ]);
    }

    // ==================== Attendance ====================
    public function checkIn(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $data = $request->validate([
            'method' => 'nullable|in:gps,qr,remote,manual',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $data['ip'] = $request->ip();
        $data['device'] = $request->userAgent();
        $data['method'] = $data['method'] ?? 'manual';

        try {
            $attendance = $this->attendanceService->checkIn($employee, $data);

            return $this->successResponse([
                'message' => 'Checked in successfully',
                'check_in_time' => $attendance->check_in,
                'status' => $attendance->status,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function checkOut(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $data = $request->validate([
            'method' => 'nullable|in:gps,qr,remote,manual',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $data['ip'] = $request->ip();
        $data['device'] = $request->userAgent();

        try {
            $attendance = $this->attendanceService->checkOut($employee, $data);

            return $this->successResponse([
                'message' => 'Checked out successfully',
                'check_out_time' => $attendance->check_out,
                'worked_hours' => $attendance->getWorkedHours(),
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function attendanceHistory(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $year = $request->integer('year', now()->year);
        $month = $request->integer('month', now()->month);

        $report = $this->attendanceService->getMonthlyReport($employee->id, $year, $month);

        return $this->successResponse($report);
    }

    // ==================== Leave Requests ====================
    public function leaveBalance(): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $balances = \App\Models\LeaveBalance::where('employee_id', $employee->id)
            ->where('year', now()->year)
            ->with('leaveType:id,name')
            ->get()
            ->map(fn($b) => [
                'leave_type' => $b->leaveType->name,
                'entitled' => $b->entitled,
                'used' => $b->used,
                'pending' => $b->pending,
                'available' => $b->available,
            ]);

        return $this->successResponse($balances);
    }

    public function myLeaveRequests(): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $requests = LeaveRequest::where('employee_id', $employee->id)
            ->with('leaveType:id,name')
            ->orderByDesc('created_at')
            ->paginate(request()->integer('per_page', 15));

        return $this->paginatedResponse($requests);
    }

    public function requestLeave(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'nullable|string|max:500',
        ]);

        $validated['employee_id'] = $employee->id;
        $validated['organization_id'] = $employee->organization_id;
        $validated['status'] = 'pending';

        $leaveRequest = LeaveRequest::create($validated);

        return $this->createdResponse($leaveRequest, 'Leave request submitted successfully');
    }

    // ==================== Payslips ====================
    public function myPayslips(): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $year = request()->integer('year');

        $payslips = Payslip::forEmployee($employee->id)
            ->when($year, fn($q) => $q->whereHas('payrollRun', fn($q2) => $q2->where('year', $year)))
            ->with('payrollRun:id,year,month,period_start,period_end')
            ->orderByDesc('created_at')
            ->paginate(request()->integer('per_page', 12));

        return $this->paginatedResponse($payslips);
    }

    public function viewPayslip(int $payslipId): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $payslip = Payslip::forEmployee($employee->id)
            ->with(['items', 'payrollRun'])
            ->find($payslipId);

        if (!$payslip) {
            return $this->notFoundResponse();
        }

        return $this->successResponse([
            'payslip' => $payslip,
            'earnings' => $payslip->getEarningsBreakdown(),
            'deductions' => $payslip->getDeductionsBreakdown(),
        ]);
    }

    // ==================== Loans ====================
    public function myLoans(): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $loans = EmployeeLoan::forEmployee($employee->id)
            ->with('payments')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse($loans);
    }

    public function requestLoan(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $validated = $request->validate([
            'type' => 'required|in:salary_advance,personal_loan,emergency,other',
            'amount' => 'required|numeric|min:100',
            'installments' => 'required|integer|min:1|max:24',
            'reason' => 'required|string|max:500',
        ]);

        $validated['organization_id'] = $employee->organization_id;
        $validated['employee_id'] = $employee->id;
        $validated['loan_number'] = EmployeeLoan::generateLoanNumber($employee->organization_id);
        $validated['remaining_amount'] = $validated['amount'];
        $validated['installment_amount'] = round($validated['amount'] / $validated['installments'], 2);
        $validated['start_date'] = now()->addMonth()->startOfMonth();
        $validated['status'] = 'pending';

        $loan = EmployeeLoan::create($validated);

        return $this->createdResponse($loan, 'Loan request submitted successfully');
    }

    // ==================== Profile ====================
    public function myProfile(): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        return $this->successResponse([
            'id' => $employee->id,
            'employee_number' => $employee->employee_number,
            'full_name' => $employee->full_name,
            'email' => $employee->email ?? $employee->user?->email,
            'phone' => $employee->phone,
            'position' => $employee->position,
            'department' => $employee->department,
            'branch' => $employee->branch?->name,
            'hire_date' => $employee->hire_date?->format('Y-m-d'),
            'basic_salary' => $employee->basic_salary,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $employee = $this->getEmployee();

        if (!$employee) {
            return $this->errorResponse('Employee profile not found', 404);
        }

        $validated = $request->validate([
            'phone' => 'nullable|string|max:20',
            'emergency_contact' => 'nullable|string|max:255',
            'emergency_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $employee->update($validated);

        return $this->successResponse($employee, 'Profile updated successfully');
    }
}
