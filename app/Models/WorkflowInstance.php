<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WorkflowInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'organization_id',
        'entity_type',
        'entity_id',
        'current_step_id',
        'status',
        'initiated_by',
        'started_at',
        'completed_at',
        'completed_by',
        'final_comments',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'meta_json' => 'array',
        ];
    }

    // ==================== Statuses ====================
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    // ==================== Relationships ====================
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class, 'instance_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    // ==================== Scopes ====================
    public function scopePending($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED]);
    }

    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeAwaitingUser($query, int $userId)
    {
        return $query->whereHas('approvals', function ($q) use ($userId) {
            $q->where('assigned_to', $userId)
              ->where('status', WorkflowApproval::STATUS_PENDING);
        });
    }

    // ==================== Helpers ====================
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS]);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_CANCELLED]);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getCurrentApprovals(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->approvals()
            ->where('step_id', $this->current_step_id)
            ->get();
    }

    public function getPendingApprovals(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->approvals()
            ->where('status', WorkflowApproval::STATUS_PENDING)
            ->get();
    }

    public function getApprovalHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->approvals()
            ->with(['step', 'actedBy', 'assignedTo'])
            ->orderBy('created_at')
            ->get();
    }

    public function getProgress(): array
    {
        $totalSteps = $this->workflow->getTotalSteps();
        $currentStepOrder = $this->currentStep?->order ?? 0;
        $completedSteps = $currentStepOrder > 0 ? $currentStepOrder - 1 : 0;

        return [
            'total_steps' => $totalSteps,
            'current_step' => $currentStepOrder,
            'completed_steps' => $completedSteps,
            'percentage' => $totalSteps > 0 ? round(($completedSteps / $totalSteps) * 100) : 0,
        ];
    }

    public function cancel(int $userId, ?string $reason = null): bool
    {
        if (!$this->isPending()) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
            'completed_by' => $userId,
            'final_comments' => $reason,
        ]);

        // Cancel all pending approvals
        $this->approvals()
            ->where('status', WorkflowApproval::STATUS_PENDING)
            ->update(['status' => WorkflowApproval::STATUS_SKIPPED]);

        return true;
    }
}
