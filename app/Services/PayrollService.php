<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryComponent;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Models\SalaryComponent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    public function __construct(
        protected AttendanceService $attendanceService,
        protected JournalService $journalService
    ) {}

    public function createPayrollRun(array $data): PayrollRun
    {
        $year = $data['year'];
        $month = $data['month'];
        
        // Check if already exists
        $existing = PayrollRun::where('organization_id', $data['organization_id'])
            ->forPeriod($year, $month)
            ->where('branch_id', $data['branch_id'] ?? null)
            ->first();

        if ($existing) {
            throw new \Exception("Payroll for {$month}/{$year} already exists");
        }

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        return PayrollRun::create([
            'organization_id' => $data['organization_id'],
            'branch_id' => $data['branch_id'] ?? null,
            'reference' => PayrollRun::generateReference($data['organization_id'], $year, $month),
            'name' => "Payroll - " . $periodStart->format('F Y'),
            'year' => $year,
            'month' => $month,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => auth()->id(),
        ]);
    }

    public function calculatePayroll(PayrollRun $payrollRun): PayrollRun
    {
        if (!$payrollRun->canBeEdited()) {
            throw new \Exception('Payroll cannot be modified');
        }

        return DB::transaction(function () use ($payrollRun) {
            $payrollRun->update(['status' => PayrollRun::STATUS_PROCESSING]);

            // Delete existing payslips
            $payrollRun->payslips()->delete();

            // Get active employees
            $employees = Employee::where('organization_id', $payrollRun->organization_id)
                ->where('status', 'active')
                ->when($payrollRun->branch_id, fn($q) => $q->where('branch_id', $payrollRun->branch_id))
                ->get();

            foreach ($employees as $employee) {
                $this->calculateEmployeePayslip($payrollRun, $employee);
            }

            $payrollRun->recalculateTotals();
            $payrollRun->update(['status' => PayrollRun::STATUS_CALCULATED]);

            return $payrollRun->fresh('payslips');
        });
    }

    protected function calculateEmployeePayslip(PayrollRun $payrollRun, Employee $employee): Payslip
    {
        // Get attendance data
        $attendanceReport = $this->attendanceService->getMonthlyReport(
            $employee->id,
            $payrollRun->year,
            $payrollRun->month
        );

        // Create payslip
        $payslip = Payslip::create([
            'payroll_run_id' => $payrollRun->id,
            'employee_id' => $employee->id,
            'payslip_number' => Payslip::generatePayslipNumber($payrollRun->id, $employee->id),
            'basic_salary' => $employee->basic_salary ?? 0,
            'working_days' => $attendanceReport['working_days'],
            'present_days' => $attendanceReport['present_days'],
            'absent_days' => $attendanceReport['absent_days'],
            'leave_days' => $attendanceReport['leave_days'],
            'overtime_hours' => round($attendanceReport['total_overtime_minutes'] / 60),
            'late_days' => $attendanceReport['late_days'],
            'status' => Payslip::STATUS_DRAFT,
            'payment_method' => $employee->payment_method,
            'bank_account' => $employee->bank_account,
        ]);

        // Calculate earnings
        $this->calculateEarnings($payslip, $employee, $attendanceReport);

        // Calculate deductions
        $this->calculateDeductions($payslip, $employee, $attendanceReport);

        // Recalculate totals
        $payslip->recalculateTotals();
        $payslip->update(['status' => Payslip::STATUS_CALCULATED]);

        return $payslip;
    }

    protected function calculateEarnings(Payslip $payslip, Employee $employee, array $attendance): void
    {
        $basicSalary = $employee->basic_salary ?? 0;

        // 1. Basic Salary (prorated if absent)
        $dailyRate = $basicSalary / $attendance['working_days'];
        $actualBasic = $dailyRate * $attendance['present_days'];
        $payslip->addEarning('Basic Salary', $actualBasic, null, 'Basic monthly salary');

        // 2. Get employee's active allowances
        $components = EmployeeSalaryComponent::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereHas('component', fn($q) => $q->earnings()->active())
            ->with('component')
            ->get();

        foreach ($components as $empComponent) {
            $component = $empComponent->component;
            $amount = $component->calculate($basicSalary, $empComponent->amount);
            
            if ($amount > 0) {
                $payslip->addEarning(
                    $component->name,
                    $amount,
                    $component->id,
                    $component->description
                );
            }
        }

        // 3. Overtime
        $overtimeHours = round($attendance['total_overtime_minutes'] / 60, 2);
        if ($overtimeHours > 0) {
            $hourlyRate = $basicSalary / ($attendance['working_days'] * 8);
            $overtimeRate = $hourlyRate * 1.5; // 150% for overtime
            $overtimeAmount = $overtimeHours * $overtimeRate;
            
            $payslip->addEarning('Overtime', $overtimeAmount, null, "{$overtimeHours} hours @ 150%");
        }
    }

    protected function calculateDeductions(Payslip $payslip, Employee $employee, array $attendance): void
    {
        $basicSalary = $employee->basic_salary ?? 0;
        $totalEarnings = $payslip->items()->where('type', 'earning')->sum('amount');

        // 1. Absence deduction
        if ($attendance['absent_days'] > 0) {
            $dailyRate = $basicSalary / $attendance['working_days'];
            $absenceDeduction = $dailyRate * $attendance['absent_days'];
            $payslip->addDeduction('Absence Deduction', $absenceDeduction, null, "{$attendance['absent_days']} days");
        }

        // 2. Late deduction (optional - some companies deduct for excessive lateness)
        // Implement based on company policy

        // 3. GOSI (Social Insurance) - Saudi Arabia: 9.75% employee, 11.75% employer
        $gosiRate = 0.0975;
        $gosiAmount = $basicSalary * $gosiRate;
        $payslip->addDeduction('GOSI', $gosiAmount, null, '9.75% of basic salary');

        // 4. Active loans
        $activeLoans = EmployeeLoan::forEmployee($employee->id)
            ->active()
            ->get();

        foreach ($activeLoans as $loan) {
            $nextPayment = $loan->getNextPayment();
            if ($nextPayment) {
                $installmentNum = $loan->paid_installments + 1;
                $payslip->addDeduction(
                    "Loan ({$loan->loan_number})",
                    $loan->installment_amount,
                    null,
                    "Installment {$installmentNum} of {$loan->installments}",
                    EmployeeLoan::class,
                    $loan->id
                );
            }
        }

        // 5. Get employee's active deductions from components
        $deductionComponents = EmployeeSalaryComponent::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->whereHas('component', fn($q) => $q->deductions()->active())
            ->with('component')
            ->get();

        foreach ($deductionComponents as $empComponent) {
            $component = $empComponent->component;
            $amount = $component->calculate($basicSalary, $empComponent->amount);
            
            if ($amount > 0) {
                $payslip->addDeduction(
                    $component->name,
                    $amount,
                    $component->id,
                    $component->description
                );
            }
        }
    }

    public function approvePayroll(PayrollRun $payrollRun): PayrollRun
    {
        if (!$payrollRun->canBeApproved()) {
            throw new \Exception('Payroll cannot be approved in current status');
        }

        $payrollRun->approve(auth()->id());

        return $payrollRun->fresh();
    }

    public function processPayment(PayrollRun $payrollRun): PayrollRun
    {
        if (!$payrollRun->canBePaid()) {
            throw new \Exception('Payroll cannot be paid in current status');
        }

        return DB::transaction(function () use ($payrollRun) {
            // Process loan deductions
            foreach ($payrollRun->payslips as $payslip) {
                $loanItems = $payslip->items()
                    ->where('reference_type', EmployeeLoan::class)
                    ->get();

                foreach ($loanItems as $item) {
                    $loan = EmployeeLoan::find($item->reference_id);
                    if ($loan) {
                        $loan->recordPayment($item->amount, $payslip->id);
                    }
                }
            }

            // Create accounting entry
            $this->createPayrollJournalEntry($payrollRun);

            $payrollRun->markAsPaid();

            return $payrollRun->fresh();
        });
    }

    protected function createPayrollJournalEntry(PayrollRun $payrollRun): void
    {
        $salaryExpenseAccountId = config('accounting.accounts.salary_expense');
        $gosiExpenseAccountId = config('accounting.accounts.gosi_expense');
        $gosiPayableAccountId = config('accounting.accounts.gosi_payable');
        $salaryPayableAccountId = config('accounting.accounts.salary_payable');

        if (!$salaryExpenseAccountId || !$salaryPayableAccountId) {
            return;
        }

        $lines = [
            [
                'account_id' => $salaryExpenseAccountId,
                'debit' => $payrollRun->total_earnings,
                'credit' => 0,
                'description' => 'Salary Expense - ' . $payrollRun->getPeriodName(),
            ],
            [
                'account_id' => $salaryPayableAccountId,
                'debit' => 0,
                'credit' => $payrollRun->total_net,
                'description' => 'Salaries Payable',
            ],
        ];

        // GOSI deduction
        $gosiTotal = $payrollRun->payslips()
            ->join('payslip_items', 'payslips.id', '=', 'payslip_items.payslip_id')
            ->where('payslip_items.name', 'GOSI')
            ->sum('payslip_items.amount');

        if ($gosiTotal > 0 && $gosiPayableAccountId) {
            $lines[] = [
                'account_id' => $gosiPayableAccountId,
                'debit' => 0,
                'credit' => $gosiTotal,
                'description' => 'GOSI Payable',
            ];
        }

        $this->journalService->createFromSource(
            $payrollRun,
            $lines,
            "Payroll - {$payrollRun->getPeriodName()}"
        );
    }

    public function getPayslip(int $payslipId): Payslip
    {
        return Payslip::with(['items', 'employee', 'payrollRun'])->findOrFail($payslipId);
    }

    public function getEmployeePayslips(int $employeeId, ?int $year = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Payslip::forEmployee($employeeId)
            ->with('payrollRun')
            ->orderByDesc('created_at');

        if ($year) {
            $query->whereHas('payrollRun', fn($q) => $q->where('year', $year));
        }

        return $query->get();
    }

    public function seedDefaultComponents(int $organizationId): void
    {
        $components = [
            ['code' => 'BASIC', 'name' => 'Basic Salary', 'type' => 'earning', 'category' => 'basic', 'calculation' => 'fixed', 'sort_order' => 1],
            ['code' => 'HOUSING', 'name' => 'Housing Allowance', 'type' => 'earning', 'category' => 'allowance', 'calculation' => 'percentage', 'percentage' => 25, 'percentage_of' => 'basic', 'sort_order' => 2],
            ['code' => 'TRANSPORT', 'name' => 'Transport Allowance', 'type' => 'earning', 'category' => 'allowance', 'calculation' => 'fixed', 'default_amount' => 1000, 'sort_order' => 3],
            ['code' => 'OVERTIME', 'name' => 'Overtime', 'type' => 'earning', 'category' => 'overtime', 'calculation' => 'formula', 'sort_order' => 4],
            ['code' => 'GOSI', 'name' => 'GOSI', 'type' => 'deduction', 'category' => 'insurance', 'calculation' => 'percentage', 'percentage' => 9.75, 'percentage_of' => 'basic', 'is_taxable' => false, 'sort_order' => 10],
            ['code' => 'LOAN', 'name' => 'Loan Deduction', 'type' => 'deduction', 'category' => 'loan', 'calculation' => 'fixed', 'sort_order' => 11],
        ];

        foreach ($components as $comp) {
            SalaryComponent::firstOrCreate(
                ['organization_id' => $organizationId, 'code' => $comp['code']],
                array_merge($comp, ['organization_id' => $organizationId, 'is_active' => true])
            );
        }
    }
}
