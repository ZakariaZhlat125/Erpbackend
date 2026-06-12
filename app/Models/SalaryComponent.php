<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'type',
        'category',
        'calculation',
        'default_amount',
        'percentage',
        'percentage_of',
        'formula',
        'is_taxable',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'default_amount' => 'decimal:2',
            'percentage' => 'decimal:4',
            'is_taxable' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ==================== Types ====================
    public const TYPE_EARNING = 'earning';
    public const TYPE_DEDUCTION = 'deduction';

    // ==================== Categories ====================
    public const CATEGORY_BASIC = 'basic';
    public const CATEGORY_ALLOWANCE = 'allowance';
    public const CATEGORY_BONUS = 'bonus';
    public const CATEGORY_OVERTIME = 'overtime';
    public const CATEGORY_DEDUCTION = 'deduction';
    public const CATEGORY_TAX = 'tax';
    public const CATEGORY_INSURANCE = 'insurance';
    public const CATEGORY_LOAN = 'loan';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function employeeComponents(): HasMany
    {
        return $this->hasMany(EmployeeSalaryComponent::class, 'component_id');
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEarnings($query)
    {
        return $query->where('type', self::TYPE_EARNING);
    }

    public function scopeDeductions($query)
    {
        return $query->where('type', self::TYPE_DEDUCTION);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // ==================== Helpers ====================
    public function isEarning(): bool
    {
        return $this->type === self::TYPE_EARNING;
    }

    public function isDeduction(): bool
    {
        return $this->type === self::TYPE_DEDUCTION;
    }

    public function calculate(float $baseSalary, ?float $customAmount = null): float
    {
        if ($customAmount !== null) {
            return $customAmount;
        }

        return match ($this->calculation) {
            'fixed' => $this->default_amount ?? 0,
            'percentage' => $baseSalary * (($this->percentage ?? 0) / 100),
            'formula' => $this->evaluateFormula($baseSalary),
            default => 0,
        };
    }

    protected function evaluateFormula(float $baseSalary): float
    {
        // Simple formula evaluation
        // Supports: basic_salary, percentage values
        $formula = str_replace('basic_salary', $baseSalary, $this->formula ?? '0');
        
        try {
            return eval("return {$formula};");
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
