<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'instance_id',
        'step_id',
        'assigned_to',
        'acted_by',
        'status',
        'comments',
        'delegated_to',
        'delegation_reason',
        'assigned_at',
        'acted_at',
        'due_at',
        'ip_address',
        'user_agent',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'acted_at' => 'datetime',
            'due_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_DELEGATED = 'delegated';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_EXPIRED = 'expired';

    // ==================== Relationships ====================
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'step_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by');
    }

    public function delegatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegated_to');
    }

    // ==================== Scopes ====================
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now());
    }

    // ==================== Helpers ====================
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isOverdue(): bool
    {
        return $this->isPending() && $this->due_at && $this->due_at->isPast();
    }

    public function canBeActedByUser(int $userId): bool
    {
        return $this->isPending() && $this->assigned_to === $userId;
    }

    public function approve(int $userId, ?string $comments = null, ?string $ip = null, ?string $userAgent = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_APPROVED,
            'acted_by' => $userId,
            'comments' => $comments,
            'acted_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function reject(int $userId, ?string $comments = null, ?string $ip = null, ?string $userAgent = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_REJECTED,
            'acted_by' => $userId,
            'comments' => $comments,
            'acted_at' => now(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function delegate(int $userId, int $delegateToId, ?string $reason = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        if (!$this->step->allow_delegation) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_DELEGATED,
            'acted_by' => $userId,
            'delegated_to' => $delegateToId,
            'delegation_reason' => $reason,
            'acted_at' => now(),
        ]);
    }

    public function getRemainingTime(): ?int
    {
        if (!$this->due_at || !$this->isPending()) {
            return null;
        }

        $remaining = $this->due_at->diffInMinutes(now(), false);
        return $remaining > 0 ? 0 : abs($remaining);
    }
}
