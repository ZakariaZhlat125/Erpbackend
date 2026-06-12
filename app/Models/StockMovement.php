<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'product_id',
        'batch_id',
        'serial_number_id',
        'type',
        'quantity',
        'unit_cost',
        'total_cost',
        'balance_before',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_cost' => 'decimal:4',
            'balance_before' => 'decimal:4',
            'balance_after' => 'decimal:4',
        ];
    }

    // ==================== Types ====================
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALE = 'sale';
    public const TYPE_TRANSFER_IN = 'transfer_in';
    public const TYPE_TRANSFER_OUT = 'transfer_out';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_RETURN = 'return';
    public const TYPE_DAMAGE = 'damage';
    public const TYPE_OPENING = 'opening';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==================== Scopes ====================
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        if ($from) $query->where('created_at', '>=', $from);
        if ($to) $query->where('created_at', '<=', $to);
        return $query;
    }

    // ==================== Helpers ====================
    public function isInbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_PURCHASE,
            self::TYPE_TRANSFER_IN,
            self::TYPE_RETURN,
            self::TYPE_OPENING,
        ]) || $this->quantity > 0;
    }

    public function isOutbound(): bool
    {
        return in_array($this->type, [
            self::TYPE_SALE,
            self::TYPE_TRANSFER_OUT,
            self::TYPE_DAMAGE,
        ]) || $this->quantity < 0;
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_PURCHASE => 'Purchase',
            self::TYPE_SALE => 'Sale',
            self::TYPE_TRANSFER_IN => 'Transfer In',
            self::TYPE_TRANSFER_OUT => 'Transfer Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
            self::TYPE_RETURN => 'Return',
            self::TYPE_DAMAGE => 'Damage',
            self::TYPE_OPENING => 'Opening Balance',
            default => $this->type,
        };
    }
}
