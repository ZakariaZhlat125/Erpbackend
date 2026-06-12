<?php

namespace App\Services\WorkflowEngine;

use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Models\WorkflowApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkflowService
{
    public function __construct(
        protected WorkflowExecutor $executor
    ) {}

    // ==================== Workflow Management ====================
    public function createWorkflow(array $data): Workflow
    {
        return DB::transaction(function () use ($data) {
            $steps = $data['steps'] ?? [];
            unset($data['steps']);

            $workflow = Workflow::create($data);

            foreach ($steps as $index => $stepData) {
                $stepData['workflow_id'] = $workflow->id;
                $stepData['order'] = $stepData['order'] ?? ($index + 1);
                WorkflowStep::create($stepData);
            }

            return $workflow->load('steps');
        });
    }

    public function updateWorkflow(Workflow $workflow, array $data): Workflow
    {
        return DB::transaction(function () use ($workflow, $data) {
            $steps = $data['steps'] ?? null;
            unset($data['steps']);

            $workflow->update($data);

            if ($steps !== null) {
                // Delete existing steps
                $workflow->steps()->delete();

                // Create new steps
                foreach ($steps as $index => $stepData) {
                    $stepData['workflow_id'] = $workflow->id;
                    $stepData['order'] = $stepData['order'] ?? ($index + 1);
                    WorkflowStep::create($stepData);
                }
            }

            return $workflow->fresh('steps');
        });
    }

    public function deleteWorkflow(Workflow $workflow): bool
    {
        // Check if there are active instances
        $activeInstances = $workflow->instances()
            ->whereIn('status', [WorkflowInstance::STATUS_PENDING, WorkflowInstance::STATUS_IN_PROGRESS])
            ->exists();

        if ($activeInstances) {
            throw new \Exception('Cannot delete workflow with active instances');
        }

        return $workflow->delete();
    }

    public function getWorkflows(int $organizationId, array $filters = []): LengthAwarePaginator
    {
        $query = Workflow::forOrganization($organizationId)
            ->with('steps');

        if (isset($filters['entity_type'])) {
            $query->forEntity($filters['entity_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', "%{$filters['search']}%");
        }

        return $query->orderBy('name')
            ->paginate($filters['per_page'] ?? 15);
    }

    // ==================== Instance Management ====================
    public function startWorkflow(Model $entity, ?int $workflowId = null): ?WorkflowInstance
    {
        $organizationId = $entity->organization_id ?? auth()->user()?->organization_id;

        if ($workflowId) {
            $workflow = Workflow::find($workflowId);
        } else {
            // Find matching workflow
            $workflow = Workflow::forOrganization($organizationId)
                ->forEntity(get_class($entity))
                ->active()
                ->first();
        }

        if (!$workflow) {
            return null;
        }

        return $this->executor->start($workflow, $entity);
    }

    public function triggerWorkflow(Model $entity, string $trigger, ?array $context = null): ?WorkflowInstance
    {
        $organizationId = $entity->organization_id ?? auth()->user()?->organization_id;

        $query = Workflow::forOrganization($organizationId)
            ->forEntity(get_class($entity))
            ->triggeredBy($trigger)
            ->active();

        // For status_changed trigger, check trigger_value
        if ($trigger === Workflow::TRIGGER_STATUS_CHANGED && isset($context['new_status'])) {
            $query->where(function ($q) use ($context) {
                $q->whereNull('trigger_value')
                  ->orWhere('trigger_value', $context['new_status']);
            });
        }

        $workflow = $query->first();

        if (!$workflow) {
            return null;
        }

        return $this->executor->start($workflow, $entity);
    }

    public function cancelInstance(WorkflowInstance $instance, ?string $reason = null): bool
    {
        return $instance->cancel(auth()->id(), $reason);
    }

    public function getInstances(int $organizationId, array $filters = []): LengthAwarePaginator
    {
        $query = WorkflowInstance::where('organization_id', $organizationId)
            ->with(['workflow', 'currentStep', 'initiator']);

        if (isset($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (isset($filters['workflow_id'])) {
            $query->where('workflow_id', $filters['workflow_id']);
        }

        if (isset($filters['entity_type'])) {
            $query->where('entity_type', $filters['entity_type']);
        }

        if (isset($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        return $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    // ==================== Approval Management ====================
    public function approve(WorkflowApproval $approval, ?string $comments = null): bool
    {
        return $this->executor->handleApproval($approval, WorkflowApproval::STATUS_APPROVED, $comments);
    }

    public function reject(WorkflowApproval $approval, ?string $comments = null): bool
    {
        return $this->executor->handleApproval($approval, WorkflowApproval::STATUS_REJECTED, $comments);
    }

    public function delegate(WorkflowApproval $approval, int $delegateToId, ?string $reason = null): bool
    {
        return $this->executor->handleDelegation($approval, $delegateToId, $reason);
    }

    public function getPendingApprovals(int $userId, ?int $organizationId = null): LengthAwarePaginator
    {
        $query = WorkflowApproval::pending()
            ->forUser($userId)
            ->with(['instance.workflow', 'instance.entity', 'step']);

        if ($organizationId) {
            $query->whereHas('instance', function ($q) use ($organizationId) {
                $q->where('organization_id', $organizationId);
            });
        }

        return $query->orderBy('due_at')
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function getMyApprovalHistory(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowApproval::where('acted_by', $userId)
            ->whereIn('status', [
                WorkflowApproval::STATUS_APPROVED,
                WorkflowApproval::STATUS_REJECTED,
                WorkflowApproval::STATUS_DELEGATED,
            ])
            ->with(['instance.workflow', 'step'])
            ->orderByDesc('acted_at')
            ->paginate($perPage);
    }

    // ==================== Statistics ====================
    public function getStatistics(int $organizationId): array
    {
        $total = WorkflowInstance::where('organization_id', $organizationId)->count();
        
        $byStatus = WorkflowInstance::where('organization_id', $organizationId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $avgCompletionTime = WorkflowInstance::where('organization_id', $organizationId)
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, started_at, completed_at)) as avg_hours')
            ->value('avg_hours');

        $pendingApprovals = WorkflowApproval::pending()
            ->whereHas('instance', fn($q) => $q->where('organization_id', $organizationId))
            ->count();

        $overdueApprovals = WorkflowApproval::overdue()
            ->whereHas('instance', fn($q) => $q->where('organization_id', $organizationId))
            ->count();

        return [
            'total_instances' => $total,
            'by_status' => $byStatus,
            'avg_completion_hours' => round($avgCompletionTime ?? 0, 1),
            'pending_approvals' => $pendingApprovals,
            'overdue_approvals' => $overdueApprovals,
        ];
    }
}
