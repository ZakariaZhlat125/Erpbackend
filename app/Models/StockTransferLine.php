<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransferLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'batch_id',
        'quantity_requested',
        'quantity_shipped',
        'quantity_received',
        'unit_cost',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_shipped' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    // ==================== Relationships ====================
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(StockTransferSerial::class, 'transfer_line_id');
    }

    // ==================== Helpers ====================
    public function getVariance(): float
    {
        return ($this->quantity_received ?? 0) - ($this->quantity_shipped ?? $this->quantity_requested);
    }

    public function isFullyReceived(): bool
    {
        return $this->quantity_received >= $this->quantity_requested;
    }
}
