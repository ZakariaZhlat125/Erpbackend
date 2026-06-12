<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WorkflowResource;
use App\Http\Resources\WorkflowInstanceResource;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\WorkflowEngine\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowController extends BaseApiController
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    // ==================== Workflow Management ====================
    public function index(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        
        $filters = request()->only(['entity_type', 'is_active', 'search', 'per_page']);
        $workflows = $this->workflowService->getWorkflows($organizationId, $filters);

        return $this->paginatedResponse($workflows, WorkflowResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entity_type' => 'required|string',
            'trigger_on' => 'required|in:created,updated,status_changed,manual',
            'trigger_field' => 'nullable|string',
            'trigger_value' => 'nullable|string',
            'is_active' => 'boolean',
            'allow_parallel' => 'boolean',
            'config_json' => 'nullable|array',
            'steps' => 'required|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approver_type' => 'required|in:user,role,field,hierarchy,any_of,all_of',
            'steps.*.approver_value' => 'required|string',
            'steps.*.condition_json' => 'nullable|array',
            'steps.*.timeout_hours' => 'nullable|integer|min:1',
            'steps.*.on_timeout' => 'nullable|in:wait,auto_approve,auto_reject,escalate,notify',
            'steps.*.on_reject' => 'nullable|in:stop,restart,goto_step,notify_only',
            'steps.*.is_optional' => 'boolean',
            'steps.*.allow_delegation' => 'boolean',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;

        $workflow = $this->workflowService->createWorkflow($validated);

        return $this->createdResponse(
            new WorkflowResource($workflow),
            'Workflow created successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $workflow = Workflow::with('steps')->find($id);

        if (!$workflow) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new WorkflowResource($workflow));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $workflow = Workflow::find($id);

        if (!$workflow) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'trigger_on' => 'sometimes|in:created,updated,status_changed,manual',
            'trigger_field' => 'nullable|string',
            'trigger_value' => 'nullable|string',
            'is_active' => 'boolean',
            'allow_parallel' => 'boolean',
            'config_json' => 'nullable|array',
            'steps' => 'sometimes|array|min:1',
            'steps.*.name' => 'required|string|max:255',
            'steps.*.approver_type' => 'required|in:user,role,field,hierarchy,any_of,all_of',
            'steps.*.approver_value' => 'required|string',
            'steps.*.condition_json' => 'nullable|array',
            'steps.*.timeout_hours' => 'nullable|integer|min:1',
            'steps.*.on_timeout' => 'nullable|in:wait,auto_approve,auto_reject,escalate,notify',
            'steps.*.on_reject' => 'nullable|in:stop,restart,goto_step,notify_only',
            'steps.*.is_optional' => 'boolean',
            'steps.*.allow_delegation' => 'boolean',
        ]);

        $workflow = $this->workflowService->updateWorkflow($workflow, $validated);

        return $this->successResponse(
            new WorkflowResource($workflow),
            'Workflow updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $workflow = Workflow::find($id);

        if (!$workflow) {
            return $this->notFoundResponse();
        }

        try {
            $this->workflowService->deleteWorkflow($workflow);
            return $this->noContentResponse();
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 409);
        }
    }

    public function toggleActive(int $id): JsonResponse
    {
        $workflow = Workflow::find($id);

        if (!$workflow) {
            return $this->notFoundResponse();
        }

        $workflow->update(['is_active' => !$workflow->is_active]);

        return $this->successResponse(
            new WorkflowResource($workflow),
            $workflow->is_active ? 'Workflow activated' : 'Workflow deactivated'
        );
    }

    // ==================== Instance Management ====================
    public function instances(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        
        $filters = request()->only([
            'status', 'workflow_id', 'entity_type', 'entity_id', 'per_page'
        ]);

        $instances = $this->workflowService->getInstances($organizationId, $filters);

        return $this->paginatedResponse($instances, WorkflowInstanceResource::class);
    }

    public function showInstance(int $id): JsonResponse
    {
        $instance = WorkflowInstance::with([
            'workflow.steps',
            'currentStep',
            'initiator',
            'approvals.step',
            'approvals.assignedTo',
            'approvals.actedBy',
        ])->find($id);

        if (!$instance) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new WorkflowInstanceResource($instance));
    }

    public function cancelInstance(int $id): JsonResponse
    {
        $instance = WorkflowInstance::find($id);

        if (!$instance) {
            return $this->notFoundResponse();
        }

        $reason = request()->input('reason');
        
        if ($this->workflowService->cancelInstance($instance, $reason)) {
            return $this->successResponse(
                new WorkflowInstanceResource($instance->fresh()),
                'Workflow cancelled successfully'
            );
        }

        return $this->errorResponse('Cannot cancel this workflow', 409);
    }

    // ==================== Statistics ====================
    public function statistics(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        $stats = $this->workflowService->getStatistics($organizationId);

        return $this->successResponse($stats);
    }

    // ==================== Entity Types ====================
    public function entityTypes(): JsonResponse
    {
        $types = [
            ['value' => 'App\\Models\\Invoice', 'label' => 'Invoice'],
            ['value' => 'App\\Models\\Payment', 'label' => 'Payment'],
            ['value' => 'App\\Models\\Employee', 'label' => 'Employee'],
            ['value' => 'App\\Models\\Project', 'label' => 'Project'],
            ['value' => 'App\\Models\\Task', 'label' => 'Task'],
        ];

        return $this->successResponse($types);
    }
}
