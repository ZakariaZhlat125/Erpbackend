<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PayslipItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payslip_id',
        'component_id',
        'name',
        'type',
        'category',
        'amount',
        'description',
        'reference_type',
        'reference_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    // ==================== Relationships ====================
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SalaryComponent::class, 'component_id');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    // ==================== Helpers ====================
    public function isEarning(): bool
    {
        return $this->type === 'earning';
    }

    public function isDeduction(): bool
    {
        return $this->type === 'deduction';
    }
}
