<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasWorkflow;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasWorkflow;

    protected $fillable = [
        'organization_id',
        'number',
        'from_warehouse_id',
        'to_warehouse_id',
        'status',
        'request_date',
        'expected_date',
        'shipped_date',
        'received_date',
        'notes',
        'shipping_notes',
        'requested_by',
        'approved_by',
        'approved_at',
        'shipped_by',
        'received_by',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'request_date' => 'date',
            'expected_date' => 'date',
            'shipped_date' => 'date',
            'received_date' => 'date',
            'approved_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_RECEIVED = 'received';
    public const STATUS_CANCELLED = 'cancelled';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class, 'transfer_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function shippedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'shipped_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // ==================== Scopes ====================
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [self::STATUS_SHIPPED, self::STATUS_IN_TRANSIT]);
    }

    // ==================== Helpers ====================
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_PENDING]);
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function canBeShipped(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canBeReceived(): bool
    {
        return in_array($this->status, [self::STATUS_SHIPPED, self::STATUS_IN_TRANSIT]);
    }

    public function submit(): bool
    {
        if (!$this->isDraft()) {
            return false;
        }
        return $this->update(['status' => self::STATUS_PENDING]);
    }

    public function approve(int $userId): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }
        return $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function ship(int $userId, ?string $notes = null): bool
    {
        if (!$this->canBeShipped()) {
            return false;
        }
        return $this->update([
            'status' => self::STATUS_SHIPPED,
            'shipped_by' => $userId,
            'shipped_date' => now(),
            'shipping_notes' => $notes,
        ]);
    }

    public function receive(int $userId): bool
    {
        if (!$this->canBeReceived()) {
            return false;
        }
        return $this->update([
            'status' => self::STATUS_RECEIVED,
            'received_by' => $userId,
            'received_date' => now(),
        ]);
    }

    public function cancel(): bool
    {
        if (in_array($this->status, [self::STATUS_RECEIVED, self::STATUS_CANCELLED])) {
            return false;
        }
        return $this->update(['status' => self::STATUS_CANCELLED]);
    }

    public function getTotalQuantity(): float
    {
        return $this->lines()->sum('quantity_requested');
    }

    public static function generateNumber(int $organizationId): string
    {
        $year = now()->format('Y');
        $prefix = "TR-{$year}-";
        
        $lastNumber = static::where('organization_id', $organizationId)
            ->where('number', 'like', $prefix . '%')
            ->orderByDesc('number')
            ->value('number');

        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, strlen($prefix));
            $newSeq = $lastSeq + 1;
        } else {
            $newSeq = 1;
        }

        return $prefix . str_pad($newSeq, 5, '0', STR_PAD_LEFT);
    }
}
