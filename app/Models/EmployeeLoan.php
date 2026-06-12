<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeLoan extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'organization_id',
        'employee_id',
        'loan_number',
        'type',
        'amount',
        'paid_amount',
        'remaining_amount',
        'installments',
        'installment_amount',
        'paid_installments',
        'start_date',
        'end_date',
        'status',
        'reason',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'installments' => 'integer',
            'paid_installments' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'approved_at' => 'datetime',
        ];
    }

    // ==================== Types ====================
    public const TYPE_SALARY_ADVANCE = 'salary_advance';
    public const TYPE_PERSONAL_LOAN = 'personal_loan';
    public const TYPE_EMERGENCY = 'emergency';
    public const TYPE_OTHER = 'other';

    // ==================== Statuses ====================
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LoanPayment::class, 'loan_id');
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // ==================== Helpers ====================
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function approve(int $userId): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        // Generate payment schedule
        $this->generatePaymentSchedule();

        $this->update(['status' => self::STATUS_ACTIVE]);

        return true;
    }

    public function generatePaymentSchedule(): void
    {
        $startDate = $this->start_date;

        for ($i = 1; $i <= $this->installments; $i++) {
            LoanPayment::create([
                'loan_id' => $this->id,
                'installment_number' => $i,
                'amount' => $this->installment_amount,
                'due_date' => $startDate->copy()->addMonths($i - 1)->endOfMonth(),
                'status' => 'pending',
            ]);
        }
    }

    public function recordPayment(float $amount, ?int $payslipId = null): void
    {
        $pendingPayment = $this->payments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->first();

        if ($pendingPayment) {
            $pendingPayment->update([
                'payslip_id' => $payslipId,
                'paid_date' => now(),
                'status' => 'paid',
            ]);
        }

        $this->paid_amount += $amount;
        $this->remaining_amount -= $amount;
        $this->paid_installments++;

        if ($this->remaining_amount <= 0) {
            $this->status = self::STATUS_COMPLETED;
            $this->end_date = now();
        }

        $this->save();
    }

    public function getNextPayment(): ?LoanPayment
    {
        return $this->payments()
            ->where('status', 'pending')
            ->orderBy('installment_number')
            ->first();
    }

    public static function generateLoanNumber(int $organizationId): string
    {
        $year = now()->format('Y');
        $prefix = "LOAN-{$year}-";
        
        $lastNumber = static::where('organization_id', $organizationId)
            ->where('loan_number', 'like', $prefix . '%')
            ->orderByDesc('loan_number')
            ->value('loan_number');

        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, strlen($prefix));
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }

        return $prefix . str_pad($newSeq, 5, '0', STR_PAD_LEFT);
    }
}
