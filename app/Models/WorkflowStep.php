<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'order',
        'name',
        'description',
        'approver_type',
        'approver_value',
        'condition_json',
        'timeout_hours',
        'on_timeout',
        'escalate_to',
        'on_reject',
        'goto_step_id',
        'is_optional',
        'allow_delegation',
        'notifications_json',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'condition_json' => 'array',
            'timeout_hours' => 'integer',
            'goto_step_id' => 'integer',
            'is_optional' => 'boolean',
            'allow_delegation' => 'boolean',
            'notifications_json' => 'array',
        ];
    }

    // ==================== Approver Types ====================
    public const APPROVER_USER = 'user';
    public const APPROVER_ROLE = 'role';
    public const APPROVER_FIELD = 'field';
    public const APPROVER_HIERARCHY = 'hierarchy';
    public const APPROVER_ANY_OF = 'any_of';
    public const APPROVER_ALL_OF = 'all_of';

    // ==================== On Timeout Actions ====================
    public const TIMEOUT_WAIT = 'wait';
    public const TIMEOUT_AUTO_APPROVE = 'auto_approve';
    public const TIMEOUT_AUTO_REJECT = 'auto_reject';
    public const TIMEOUT_ESCALATE = 'escalate';
    public const TIMEOUT_NOTIFY = 'notify';

    // ==================== On Reject Actions ====================
    public const REJECT_STOP = 'stop';
    public const REJECT_RESTART = 'restart';
    public const REJECT_GOTO_STEP = 'goto_step';
    public const REJECT_NOTIFY_ONLY = 'notify_only';

    // ==================== Relationships ====================
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function gotoStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'goto_step_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class, 'step_id');
    }

    // ==================== Helpers ====================
    public function isFirstStep(): bool
    {
        return $this->order === 1;
    }

    public function isLastStep(): bool
    {
        return !$this->workflow->steps()
            ->where('order', '>', $this->order)
            ->exists();
    }

    public function getNextStep(): ?WorkflowStep
    {
        return $this->workflow->getNextStep($this);
    }

    public function getPreviousStep(): ?WorkflowStep
    {
        return $this->workflow->steps()
            ->where('order', '<', $this->order)
            ->orderByDesc('order')
            ->first();
    }

    public function getDueDate(): ?\DateTime
    {
        if (!$this->timeout_hours) {
            return null;
        }

        return now()->addHours($this->timeout_hours);
    }

    public function requiresAllApprovers(): bool
    {
        return $this->approver_type === self::APPROVER_ALL_OF;
    }

    public function hasCondition(): bool
    {
        return !empty($this->condition_json);
    }
}
