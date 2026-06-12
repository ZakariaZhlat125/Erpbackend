<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalBatch extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'organization_id',
        'branch_id',
        'number',
        'date',
        'reference',
        'description',
        'type',
        'status',
        'source_type',
        'source_id',
        'reversed_batch_id',
        'currency_code',
        'exchange_rate',
        'total_debit',
        'total_credit',
        'created_by',
        'posted_by',
        'posted_at',
        'voided_by',
        'voided_at',
        'void_reason',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'exchange_rate' => 'decimal:6',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'posted_at' => 'datetime',
            'voided_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    // ==================== Types ====================
    public const TYPE_MANUAL = 'manual';
    public const TYPE_AUTO = 'auto';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_CLOSING = 'closing';
    public const TYPE_OPENING = 'opening';
    public const TYPE_REVERSAL = 'reversal';

    // ==================== Statuses ====================
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'batch_id')->orderBy('line_number');
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'source_type', 'source_id');
    }

    public function reversedBatch(): BelongsTo
    {
        return $this->belongsTo(JournalBatch::class, 'reversed_batch_id');
    }

    public function reversalBatch(): HasMany
    {
        return $this->hasMany(JournalBatch::class, 'reversed_batch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    // ==================== Scopes ====================
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBetweenDates($query, ?string $from, ?string $to)
    {
        if ($from) $query->where('date', '>=', $from);
        if ($to) $query->where('date', '<=', $to);
        return $query;
    }

    // ==================== Helpers ====================
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOIDED;
    }

    public function canBeEdited(): bool
    {
        return $this->isDraft();
    }

    public function canBePosted(): bool
    {
        return $this->isDraft() && $this->isBalanced();
    }

    public function canBeVoided(): bool
    {
        return $this->isPosted();
    }

    public function isBalanced(): bool
    {
        return abs($this->total_debit - $this->total_credit) < 0.01;
    }

    public function recalculateTotals(): void
    {
        $this->update([
            'total_debit' => $this->lines()->sum('debit'),
            'total_credit' => $this->lines()->sum('credit'),
        ]);
    }

    public function post(int $userId): bool
    {
        if (!$this->canBePosted()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_POSTED,
            'posted_by' => $userId,
            'posted_at' => now(),
        ]);
    }

    public function void(int $userId, ?string $reason = null): bool
    {
        if (!$this->canBeVoided()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_VOIDED,
            'voided_by' => $userId,
            'voided_at' => now(),
            'void_reason' => $reason,
        ]);
    }

    public function getLinesSummary(): array
    {
        return $this->lines()
            ->with(['account:id,code,name', 'costCenter:id,code,name'])
            ->get()
            ->map(fn($line) => [
                'account' => $line->account->code . ' - ' . $line->account->name,
                'cost_center' => $line->costCenter?->name,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'description' => $line->description,
            ])
            ->toArray();
    }

    public static function generateNumber(int $organizationId): string
    {
        $year = now()->format('Y');
        $prefix = "JV-{$year}-";
        
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
