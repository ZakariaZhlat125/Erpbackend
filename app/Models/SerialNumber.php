<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SerialNumber extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'product_id',
        'warehouse_id',
        'serial_number',
        'status',
        'purchase_invoice_id',
        'sales_invoice_id',
        'purchase_date',
        'warranty_expiry',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'warranty_expiry' => 'date',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_SOLD = 'sold';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_DAMAGED = 'damaged';
    public const STATUS_EXPIRED = 'expired';

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

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'sales_invoice_id');
    }

    // ==================== Scopes ====================
    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public function scopeInWarehouse($query, int $warehouseId)
    {
        return $query->where('warehouse_id', $warehouseId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    // ==================== Helpers ====================
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_AVAILABLE;
    }

    public function reserve(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }
        return $this->update(['status' => self::STATUS_RESERVED]);
    }

    public function sell(int $invoiceId): bool
    {
        return $this->update([
            'status' => self::STATUS_SOLD,
            'sales_invoice_id' => $invoiceId,
        ]);
    }

    public function transfer(int $warehouseId): bool
    {
        return $this->update(['warehouse_id' => $warehouseId]);
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_expiry && $this->warranty_expiry->isFuture();
    }

    public static function generateSerial(int $productId, ?string $prefix = null): string
    {
        $prefix = $prefix ?? 'SN';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        
        return "{$prefix}-{$productId}-{$timestamp}-{$random}";
    }
}
