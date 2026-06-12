<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TaxTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'code',
        'name',
        'description',
        'applies_to',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TaxTemplateLine::class)->orderBy('order');
    }

    public function taxRates(): BelongsToMany
    {
        return $this->belongsToMany(TaxRate::class, 'tax_template_lines')
            ->withPivot('order')
            ->orderBy('pivot_order');
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSales($query)
    {
        return $query->whereIn('applies_to', ['sales', 'both']);
    }

    public function scopeForPurchase($query)
    {
        return $query->whereIn('applies_to', ['purchase', 'both']);
    }

    // ==================== Helpers ====================
    public function calculateTaxes(float $amount, bool $isInclusive = false): array
    {
        $results = [];
        $runningAmount = $amount;

        foreach ($this->taxRates as $taxRate) {
            $calculation = $taxRate->calculateTax($runningAmount, $isInclusive);
            $results[] = $calculation;

            // For compound taxes, calculate on top of previous
            if ($taxRate->is_compound) {
                $runningAmount = $calculation['gross_amount'];
            }
        }

        $totalTax = array_sum(array_column($results, 'tax_amount'));

        return [
            'net_amount' => round($amount, 2),
            'total_tax' => round($totalTax, 2),
            'gross_amount' => round($amount + $totalTax, 2),
            'taxes' => $results,
        ];
    }

    public function getTotalRate(): float
    {
        return $this->taxRates->sum('rate');
    }
}
