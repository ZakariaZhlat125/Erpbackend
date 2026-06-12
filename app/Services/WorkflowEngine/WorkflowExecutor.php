<?php

namespace App\Services\WorkflowEngine;

use App\Models\Workflow;
use App\Models\WorkflowApproval;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Events\WorkflowStarted;
use App\Events\WorkflowStepCompleted;
use App\Events\WorkflowCompleted;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WorkflowExecutor
{
    public function __construct(
        protected ConditionEvaluator $conditionEvaluator,
        protected ApproverResolver $approverResolver,
        protected ActivityLogService $activityLogService
    ) {}

    public function start(Workflow $workflow, Model $entity, ?int $initiatedBy = null): ?WorkflowInstance
    {
        if (!$workflow->canStartNewInstance($entity)) {
            return null;
        }

        return DB::transaction(function () use ($workflow, $entity, $initiatedBy) {
            // Create instance
            $instance = WorkflowInstance::create([
                'workflow_id' => $workflow->id,
                'organization_id' => $entity->organization_id ?? $workflow->organization_id,
                'entity_type' => get_class($entity),
                'entity_id' => $entity->getKey(),
                'status' => WorkflowInstance::STATUS_IN_PROGRESS,
                'initiated_by' => $initiatedBy ?? auth()->id(),
                'started_at' => now(),
            ]);

            // Start first step
            $firstStep = $workflow->getFirstStep();
            if ($firstStep) {
                $this->processStep($instance, $firstStep, $entity);
            } else {
                // No steps, auto-approve
                $this->completeInstance($instance, WorkflowInstance::STATUS_APPROVED);
            }

            // Fire event
            event(new WorkflowStarted($instance));

            // Log activity
            $this->activityLogService->logCustomAction(
                'workflow_started',
                $entity,
                null,
                ['workflow_id' => $workflow->id, 'instance_id' => $instance->id]
            );

            return $instance;
        });
    }

    public function processStep(WorkflowInstance $instance, WorkflowStep $step, ?Model $entity = null): void
    {
        $entity = $entity ?? $instance->entity;

        // Check condition
        if (!$this->conditionEvaluator->evaluate($entity, $step->condition_json)) {
            // Condition not met, skip to next step
            $this->moveToNextStep($instance, $step);
            return;
        }

        // Update current step
        $instance->update(['current_step_id' => $step->id]);

        // Resolve approvers
        $approvers = $this->approverResolver->resolve($step, $entity);

        if ($approvers->isEmpty()) {
            // No approvers found
            if ($step->is_optional) {
                $this->moveToNextStep($instance, $step);
            } else {
                Log::warning("Workflow step has no approvers", [
                    'instance_id' => $instance->id,
                    'step_id' => $step->id,
                ]);
            }
            return;
        }

        // Create approval records
        $dueAt = $step->getDueDate();

        foreach ($approvers as $approver) {
            WorkflowApproval::create([
                'instance_id' => $instance->id,
                'step_id' => $step->id,
                'assigned_to' => $approver->id,
                'status' => WorkflowApproval::STATUS_PENDING,
                'assigned_at' => now(),
                'due_at' => $dueAt,
            ]);
        }

        // TODO: Send notifications to approvers
    }

    public function handleApproval(WorkflowApproval $approval, string $action, ?string $comments = null): bool
    {
        if (!$approval->isPending()) {
            return false;
        }

        $instance = $approval->instance;
        $step = $approval->step;
        $context = ActivityLogService::getContext();

        return DB::transaction(function () use ($approval, $action, $comments, $instance, $step, $context) {
            // Update approval
            $approval->update([
                'status' => $action,
                'acted_by' => auth()->id(),
                'comments' => $comments,
                'acted_at' => now(),
                'ip_address' => $context['ip'],
                'user_agent' => $context['user_agent'],
            ]);

            if ($action === WorkflowApproval::STATUS_REJECTED) {
                return $this->handleRejection($instance, $step, $comments);
            }

            if ($action === WorkflowApproval::STATUS_APPROVED) {
                return $this->handleApprovalSuccess($instance, $step);
            }

            return true;
        });
    }

    protected function handleApprovalSuccess(WorkflowInstance $instance, WorkflowStep $step): bool
    {
        // Check if all required approvals are done
        if ($step->requiresAllApprovers()) {
            $pendingCount = $instance->approvals()
                ->where('step_id', $step->id)
                ->where('status', WorkflowApproval::STATUS_PENDING)
                ->count();

            if ($pendingCount > 0) {
                // Wait for other approvers
                return true;
            }
        }

        // Mark other pending approvals as skipped (for any_of type)
        $instance->approvals()
            ->where('step_id', $step->id)
            ->where('status', WorkflowApproval::STATUS_PENDING)
            ->update(['status' => WorkflowApproval::STATUS_SKIPPED]);

        // Fire step completed event
        event(new WorkflowStepCompleted($instance, $step));

        // Move to next step
        $this->moveToNextStep($instance, $step);

        return true;
    }

    protected function handleRejection(WorkflowInstance $instance, WorkflowStep $step, ?string $comments): bool
    {
        // Cancel other pending approvals for this step
        $instance->approvals()
            ->where('step_id', $step->id)
            ->where('status', WorkflowApproval::STATUS_PENDING)
            ->update(['status' => WorkflowApproval::STATUS_SKIPPED]);

        switch ($step->on_reject) {
            case WorkflowStep::REJECT_STOP:
                $this->completeInstance($instance, WorkflowInstance::STATUS_REJECTED, $comments);
                break;

            case WorkflowStep::REJECT_RESTART:
                // Cancel all and restart
                $instance->approvals()->update(['status' => WorkflowApproval::STATUS_SKIPPED]);
                $firstStep = $instance->workflow->getFirstStep();
                if ($firstStep) {
                    $this->processStep($instance, $firstStep);
                }
                break;

            case WorkflowStep::REJECT_GOTO_STEP:
                if ($step->goto_step_id) {
                    $gotoStep = WorkflowStep::find($step->goto_step_id);
                    if ($gotoStep) {
                        $this->processStep($instance, $gotoStep);
                    }
                }
                break;

            case WorkflowStep::REJECT_NOTIFY_ONLY:
                // Just notify, don't stop the workflow
                // TODO: Send notification
                break;
        }

        return true;
    }

    protected function moveToNextStep(WorkflowInstance $instance, WorkflowStep $currentStep): void
    {
        $nextStep = $currentStep->getNextStep();

        if ($nextStep) {
            $this->processStep($instance, $nextStep);
        } else {
            // No more steps, workflow is complete
            $this->completeInstance($instance, WorkflowInstance::STATUS_APPROVED);
        }
    }

    protected function completeInstance(WorkflowInstance $instance, string $status, ?string $comments = null): void
    {
        $instance->update([
            'status' => $status,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
            'final_comments' => $comments,
        ]);

        // Fire completed event
        event(new WorkflowCompleted($instance));

        // Log activity
        $entity = $instance->entity;
        if ($entity) {
            $this->activityLogService->logCustomAction(
                $status === WorkflowInstance::STATUS_APPROVED ? 'workflow_approved' : 'workflow_rejected',
                $entity,
                null,
                [
                    'workflow_id' => $instance->workflow_id,
                    'instance_id' => $instance->id,
                    'final_status' => $status,
                ]
            );
        }

        // Execute post-workflow actions
        $this->executePostActions($instance);
    }

    protected function executePostActions(WorkflowInstance $instance): void
    {
        $config = $instance->workflow->config_json ?? [];
        $entity = $instance->entity;

        if (!$entity) {
            return;
        }

        if ($instance->isApproved()) {
            // Execute approved actions
            $approvedActions = $config['on_approved'] ?? [];
            
            if (isset($approvedActions['set_field'])) {
                $entity->update([
                    $approvedActions['set_field']['field'] => $approvedActions['set_field']['value']
                ]);
            }

            if (isset($approvedActions['set_status'])) {
                $entity->update(['status' => $approvedActions['set_status']]);
            }
        }

        if ($instance->isRejected()) {
            // Execute rejected actions
            $rejectedActions = $config['on_rejected'] ?? [];
            
            if (isset($rejectedActions['set_status'])) {
                $entity->update(['status' => $rejectedActions['set_status']]);
            }
        }
    }

    public function handleDelegation(WorkflowApproval $approval, int $delegateToId, ?string $reason = null): bool
    {
        if (!$approval->delegate(auth()->id(), $delegateToId, $reason)) {
            return false;
        }

        // Create new approval for delegated user
        WorkflowApproval::create([
            'instance_id' => $approval->instance_id,
            'step_id' => $approval->step_id,
            'assigned_to' => $delegateToId,
            'status' => WorkflowApproval::STATUS_PENDING,
            'assigned_at' => now(),
            'due_at' => $approval->due_at,
            'meta_json' => [
                'delegated_from' => $approval->id,
                'original_assignee' => $approval->assigned_to,
            ],
        ]);

        return true;
    }
}
