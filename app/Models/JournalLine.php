<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'account_id',
        'cost_center_id',
        'line_number',
        'description',
        'debit',
        'credit',
        'debit_fc',
        'credit_fc',
        'currency_code',
        'exchange_rate',
        'party_id',
        'tax_rate_id',
        'tax_amount',
        'reference',
        'due_date',
        'dimensions_json',
    ];

    protected function casts(): array
    {
        return [
            'line_number' => 'integer',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'debit_fc' => 'decimal:2',
            'credit_fc' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'tax_amount' => 'decimal:2',
            'due_date' => 'date',
            'dimensions_json' => 'array',
        ];
    }

    // ==================== Relationships ====================
    public function batch(): BelongsTo
    {
        return $this->belongsTo(JournalBatch::class, 'batch_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    // ==================== Scopes ====================
    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByCostCenter($query, int $costCenterId)
    {
        return $query->where('cost_center_id', $costCenterId);
    }

    public function scopePosted($query)
    {
        return $query->whereHas('batch', fn($q) => $q->posted());
    }

    // ==================== Helpers ====================
    public function isDebit(): bool
    {
        return $this->debit > 0;
    }

    public function isCredit(): bool
    {
        return $this->credit > 0;
    }

    public function getAmount(): float
    {
        return $this->debit > 0 ? $this->debit : -$this->credit;
    }

    public function getNetAmount(): float
    {
        return $this->debit - $this->credit;
    }
}
