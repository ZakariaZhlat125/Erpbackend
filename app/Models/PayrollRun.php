<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollRun extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'reference',
        'name',
        'year',
        'month',
        'period_start',
        'period_end',
        'payment_date',
        'status',
        'employee_count',
        'total_earnings',
        'total_deductions',
        'total_net',
        'created_by',
        'approved_by',
        'approved_at',
        'notes',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'payment_date' => 'date',
            'employee_count' => 'integer',
            'total_earnings' => 'decimal:2',
            'total_deductions' => 'decimal:2',
            'total_net' => 'decimal:2',
            'approved_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_CALCULATED = 'calculated';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ==================== Scopes ====================
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    // ==================== Helpers ====================
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isCalculated(): bool
    {
        return $this->status === self::STATUS_CALCULATED;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_CALCULATED]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_CALCULATED;
    }

    public function canBePaid(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function recalculateTotals(): void
    {
        $totals = $this->payslips()
            ->selectRaw('COUNT(*) as count, SUM(total_earnings) as earnings, SUM(total_deductions) as deductions, SUM(net_salary) as net')
            ->first();

        $this->update([
            'employee_count' => $totals->count ?? 0,
            'total_earnings' => $totals->earnings ?? 0,
            'total_deductions' => $totals->deductions ?? 0,
            'total_net' => $totals->net ?? 0,
        ]);
    }

    public function approve(int $userId): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->payslips()->update(['status' => Payslip::STATUS_APPROVED]);

        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(): bool
    {
        if (!$this->canBePaid()) {
            return false;
        }

        $this->payslips()->update([
            'status' => Payslip::STATUS_PAID,
            'paid_date' => now(),
        ]);

        return $this->update([
            'status' => self::STATUS_PAID,
            'payment_date' => now(),
        ]);
    }

    public function getPeriodName(): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return $months[$this->month] . ' ' . $this->year;
    }

    public static function generateReference(int $organizationId, int $year, int $month): string
    {
        return "PR-{$year}-" . str_pad($month, 2, '0', STR_PAD_LEFT);
    }
}
