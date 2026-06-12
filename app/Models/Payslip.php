<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'payslip_number',
        'basic_salary',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'late_days',
        'status',
        'payment_method',
        'bank_account',
        'paid_date',
        'notes',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'working_days' => 'integer',
            'present_days' => 'integer',
            'absent_days' => 'integer',
            'leave_days' => 'integer',
            'overtime_hours' => 'integer',
            'late_days' => 'integer',
            'paid_date' => 'date',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_DRAFT = 'draft';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';

    // ==================== Relationships ====================
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayslipItem::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(PayslipItem::class)->where('type', 'earning');
    }

    public function deductions(): HasMany
    {
        return $this->hasMany(PayslipItem::class)->where('type', 'deduction');
    }

    // ==================== Scopes ====================
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    // ==================== Helpers ====================
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function addEarning(string $name, float $amount, ?int $componentId = null, ?string $description = null): PayslipItem
    {
        return $this->items()->create([
            'component_id' => $componentId,
            'name' => $name,
            'type' => 'earning',
            'amount' => $amount,
            'description' => $description,
        ]);
    }

    public function addDeduction(string $name, float $amount, ?int $componentId = null, ?string $description = null, ?string $refType = null, ?int $refId = null): PayslipItem
    {
        return $this->items()->create([
            'component_id' => $componentId,
            'name' => $name,
            'type' => 'deduction',
            'amount' => $amount,
            'description' => $description,
            'reference_type' => $refType,
            'reference_id' => $refId,
        ]);
    }

    public function recalculateTotals(): void
    {
        $this->total_earnings = $this->items()->where('type', 'earning')->sum('amount');
        $this->total_deductions = $this->items()->where('type', 'deduction')->sum('amount');
        $this->net_salary = $this->total_earnings - $this->total_deductions;
        $this->save();
    }

    public function getEarningsBreakdown(): array
    {
        return $this->items()
            ->where('type', 'earning')
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'amount' => $item->amount,
                'category' => $item->category,
            ])
            ->toArray();
    }

    public function getDeductionsBreakdown(): array
    {
        return $this->items()
            ->where('type', 'deduction')
            ->get()
            ->map(fn($item) => [
                'name' => $item->name,
                'amount' => $item->amount,
                'category' => $item->category,
            ])
            ->toArray();
    }

    public static function generatePayslipNumber(int $payrollRunId, int $employeeId): string
    {
        return "PS-{$payrollRunId}-{$employeeId}";
    }
}
