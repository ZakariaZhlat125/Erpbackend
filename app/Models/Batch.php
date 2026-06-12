<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batch extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'product_id',
        'warehouse_id',
        'batch_number',
        'quantity',
        'reserved_quantity',
        'manufacturing_date',
        'expiry_date',
        'cost_per_unit',
        'status',
        'purchase_invoice_id',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'reserved_quantity' => 'decimal:4',
            'manufacturing_date' => 'date',
            'expiry_date' => 'date',
            'cost_per_unit' => 'decimal:4',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_ACTIVE = 'active';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_DEPLETED = 'depleted';
    public const STATUS_RECALLED = 'recalled';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'purchase_invoice_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
              ->orWhere('expiry_date', '>', now());
        });
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('expiry_date', '>', now());
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeWithAvailableStock($query)
    {
        return $query->whereRaw('quantity - reserved_quantity > 0');
    }

    // ==================== Helpers ====================
    public function getAvailableQuantity(): float
    {
        return $this->quantity - $this->reserved_quantity;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date 
            && $this->expiry_date->isFuture() 
            && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }
        return max(0, $this->expiry_date->diffInDays(now(), false));
    }

    public function reserve(float $quantity): bool
    {
        if ($quantity > $this->getAvailableQuantity()) {
            return false;
        }

        return $this->update([
            'reserved_quantity' => $this->reserved_quantity + $quantity,
        ]);
    }

    public function releaseReservation(float $quantity): bool
    {
        $newReserved = max(0, $this->reserved_quantity - $quantity);
        return $this->update(['reserved_quantity' => $newReserved]);
    }

    public function deduct(float $quantity): bool
    {
        if ($quantity > $this->quantity) {
            return false;
        }

        $newQuantity = $this->quantity - $quantity;
        $status = $newQuantity <= 0 ? self::STATUS_DEPLETED : $this->status;

        return $this->update([
            'quantity' => $newQuantity,
            'reserved_quantity' => max(0, $this->reserved_quantity - $quantity),
            'status' => $status,
        ]);
    }

    public function add(float $quantity): bool
    {
        return $this->update([
            'quantity' => $this->quantity + $quantity,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    public static function generateBatchNumber(int $productId): string
    {
        $date = now()->format('Ymd');
        $count = static::where('product_id', $productId)
            ->whereDate('created_at', today())
            ->count() + 1;

        return "BTH-{$productId}-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
