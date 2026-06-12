<?php

namespace App\Traits;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\WorkflowEngine\WorkflowService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasWorkflow
{
    protected static array $workflowStatusField = [];

    public static function bootHasWorkflow(): void
    {
        static::created(function ($model) {
            static::triggerWorkflowEvent($model, Workflow::TRIGGER_CREATED);
        });

        static::updated(function ($model) {
            // Check if status changed
            $statusField = static::getWorkflowStatusField();
            if ($model->wasChanged($statusField)) {
                static::triggerWorkflowEvent($model, Workflow::TRIGGER_STATUS_CHANGED, [
                    'old_status' => $model->getOriginal($statusField),
                    'new_status' => $model->$statusField,
                ]);
            }

            static::triggerWorkflowEvent($model, Workflow::TRIGGER_UPDATED);
        });
    }

    protected static function triggerWorkflowEvent($model, string $trigger, array $context = []): void
    {
        try {
            $service = app(WorkflowService::class);
            $service->triggerWorkflow($model, $trigger, $context);
        } catch (\Exception $e) {
            \Log::error('Failed to trigger workflow', [
                'model' => get_class($model),
                'id' => $model->getKey(),
                'trigger' => $trigger,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ==================== Relationships ====================
    public function workflowInstances(): MorphMany
    {
        return $this->morphMany(WorkflowInstance::class, 'entity', 'entity_type', 'entity_id');
    }

    // ==================== Helpers ====================
    public function startWorkflow(?int $workflowId = null): ?WorkflowInstance
    {
        $service = app(WorkflowService::class);
        return $service->startWorkflow($this, $workflowId);
    }

    public function getActiveWorkflowInstance(): ?WorkflowInstance
    {
        return $this->workflowInstances()
            ->whereIn('status', [WorkflowInstance::STATUS_PENDING, WorkflowInstance::STATUS_IN_PROGRESS])
            ->latest()
            ->first();
    }

    public function hasActiveWorkflow(): bool
    {
        return $this->getActiveWorkflowInstance() !== null;
    }

    public function getWorkflowStatus(): ?string
    {
        $instance = $this->getActiveWorkflowInstance();
        return $instance?->status;
    }

    public function getWorkflowProgress(): ?array
    {
        $instance = $this->getActiveWorkflowInstance();
        return $instance?->getProgress();
    }

    public function getPendingApprovers(): array
    {
        $instance = $this->getActiveWorkflowInstance();
        if (!$instance) {
            return [];
        }

        return $instance->getPendingApprovals()
            ->pluck('assignedTo')
            ->filter()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->toArray();
    }

    public function cancelWorkflow(?string $reason = null): bool
    {
        $instance = $this->getActiveWorkflowInstance();
        if (!$instance) {
            return false;
        }

        $service = app(WorkflowService::class);
        return $service->cancelInstance($instance, $reason);
    }

    public function getWorkflowHistory(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->workflowInstances()
            ->with(['workflow', 'approvals.step', 'approvals.actedBy'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function isWorkflowApproved(): bool
    {
        $instance = $this->workflowInstances()
            ->where('status', WorkflowInstance::STATUS_APPROVED)
            ->exists();

        return $instance;
    }

    public function isWorkflowRejected(): bool
    {
        $instance = $this->workflowInstances()
            ->latest()
            ->first();

        return $instance?->status === WorkflowInstance::STATUS_REJECTED;
    }

    public function requiresApproval(): bool
    {
        $organizationId = $this->organization_id ?? auth()->user()?->organization_id;

        return Workflow::forOrganization($organizationId)
            ->forEntity(static::class)
            ->active()
            ->exists();
    }

    // ==================== Configuration ====================
    public static function getWorkflowStatusField(): string
    {
        return static::$workflowStatusField[static::class] ?? 'status';
    }

    public static function setWorkflowStatusField(string $field): void
    {
        static::$workflowStatusField[static::class] = $field;
    }
}
