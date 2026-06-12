<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'product_id',
        'quantity',
        'reserved_quantity',
        'available_quantity',
        'average_cost',
        'total_value',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'available_quantity' => 'decimal:4',
            'average_cost' => 'decimal:4',
            'total_value' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ];
    }

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

    // ==================== Scopes ====================
    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeLowStock($query, float $threshold = 10)
    {
        return $query->where('available_quantity', '<=', $threshold)
            ->where('quantity', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('available_quantity', '<=', 0);
    }

    // ==================== Helpers ====================
    public function recalculate(): void
    {
        $this->available_quantity = $this->quantity - $this->reserved_quantity;
        $this->total_value = $this->quantity * ($this->average_cost ?? 0);
        $this->save();
    }

    public function addStock(float $quantity, ?float $unitCost = null): void
    {
        if ($unitCost && $this->quantity > 0) {
            // Weighted average cost
            $totalCost = ($this->quantity * $this->average_cost) + ($quantity * $unitCost);
            $this->average_cost = $totalCost / ($this->quantity + $quantity);
        } elseif ($unitCost) {
            $this->average_cost = $unitCost;
        }

        $this->quantity += $quantity;
        $this->last_movement_at = now();
        $this->recalculate();
    }

    public function removeStock(float $quantity): bool
    {
        if ($quantity > $this->available_quantity) {
            return false;
        }

        $this->quantity -= $quantity;
        $this->last_movement_at = now();
        $this->recalculate();
        return true;
    }

    public function reserve(float $quantity): bool
    {
        if ($quantity > $this->available_quantity) {
            return false;
        }

        $this->reserved_quantity += $quantity;
        $this->recalculate();
        return true;
    }

    public function releaseReservation(float $quantity): void
    {
        $this->reserved_quantity = max(0, $this->reserved_quantity - $quantity);
        $this->recalculate();
    }

    public static function getOrCreate(int $warehouseId, int $productId, int $organizationId): self
    {
        return static::firstOrCreate(
            [
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
            ],
            [
                'organization_id' => $organizationId,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0,
            ]
        );
    }
}
