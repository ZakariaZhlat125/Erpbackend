<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'rate',
        'type',
        'calculation',
        'sales_account_id',
        'purchase_account_id',
        'is_compound',
        'is_inclusive',
        'is_default',
        'is_active',
        'effective_from',
        'effective_to',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:4',
            'is_compound' => 'boolean',
            'is_inclusive' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'config_json' => 'array',
        ];
    }

    // ==================== Types ====================
    public const TYPE_SALES = 'sales';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_WITHHOLDING = 'withholding';
    public const TYPE_EXEMPT = 'exempt';
    public const TYPE_ZERO_RATED = 'zero_rated';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function salesAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'sales_account_id');
    }

    public function purchaseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'purchase_account_id');
    }

    public function templateLines(): HasMany
    {
        return $this->hasMany(TaxTemplateLine::class);
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEffective($query, ?string $date = null)
    {
        $date = $date ?? now()->toDateString();
        
        return $query->where(function ($q) use ($date) {
            $q->whereNull('effective_from')
              ->orWhere('effective_from', '<=', $date);
        })->where(function ($q) use ($date) {
            $q->whereNull('effective_to')
              ->orWhere('effective_to', '>=', $date);
        });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForSales($query)
    {
        return $query->whereIn('type', [self::TYPE_SALES, self::TYPE_EXEMPT, self::TYPE_ZERO_RATED]);
    }

    public function scopeForPurchase($query)
    {
        return $query->whereIn('type', [self::TYPE_PURCHASE, self::TYPE_WITHHOLDING, self::TYPE_EXEMPT, self::TYPE_ZERO_RATED]);
    }

    // ==================== Helpers ====================
    public function calculateTax(float $amount, bool $isInclusive = null): array
    {
        $inclusive = $isInclusive ?? $this->is_inclusive;
        $rate = $this->rate / 100;

        if ($inclusive) {
            $taxAmount = $amount - ($amount / (1 + $rate));
            $netAmount = $amount - $taxAmount;
        } else {
            $taxAmount = $amount * $rate;
            $netAmount = $amount;
        }

        return [
            'net_amount' => round($netAmount, 2),
            'tax_amount' => round($taxAmount, 2),
            'gross_amount' => round($netAmount + $taxAmount, 2),
            'rate' => $this->rate,
            'tax_rate_id' => $this->id,
        ];
    }

    public function isEffective(?string $date = null): bool
    {
        $date = $date ?? now()->toDateString();

        if ($this->effective_from && $this->effective_from > $date) {
            return false;
        }

        if ($this->effective_to && $this->effective_to < $date) {
            return false;
        }

        return true;
    }

    public function isExempt(): bool
    {
        return $this->type === self::TYPE_EXEMPT;
    }

    public function isZeroRated(): bool
    {
        return $this->type === self::TYPE_ZERO_RATED;
    }

    public function isWithholding(): bool
    {
        return $this->type === self::TYPE_WITHHOLDING;
    }

    public function getDisplayRate(): string
    {
        return number_format($this->rate, 2) . '%';
    }
}
