<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\WorkflowApprovalResource;
use App\Models\WorkflowApproval;
use App\Services\WorkflowEngine\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WorkflowApprovalController extends BaseApiController
{
    public function __construct(
        protected WorkflowService $workflowService
    ) {}

    public function pending(): JsonResponse
    {
        $userId = Auth::id();
        $organizationId = Auth::user()->organization_id;

        $approvals = $this->workflowService->getPendingApprovals($userId, $organizationId);

        return $this->paginatedResponse($approvals, WorkflowApprovalResource::class);
    }

    public function history(): JsonResponse
    {
        $userId = Auth::id();
        $perPage = request()->integer('per_page', 15);

        $approvals = $this->workflowService->getMyApprovalHistory($userId, $perPage);

        return $this->paginatedResponse($approvals, WorkflowApprovalResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $approval = WorkflowApproval::with([
            'instance.workflow',
            'instance.entity',
            'step',
            'assignedTo',
            'actedBy',
        ])->find($id);

        if (!$approval) {
            return $this->notFoundResponse();
        }

        // Check if user can view this approval
        if ($approval->assigned_to !== Auth::id() && $approval->acted_by !== Auth::id()) {
            return $this->errorResponse('Unauthorized', 403);
        }

        return $this->successResponse(new WorkflowApprovalResource($approval));
    }

    public function approve(int $id): JsonResponse
    {
        $approval = WorkflowApproval::find($id);

        if (!$approval) {
            return $this->notFoundResponse();
        }

        if (!$approval->canBeActedByUser(Auth::id())) {
            return $this->errorResponse('You cannot approve this request', 403);
        }

        $comments = request()->input('comments');

        if ($this->workflowService->approve($approval, $comments)) {
            return $this->successResponse(
                new WorkflowApprovalResource($approval->fresh(['instance', 'step'])),
                'Approved successfully'
            );
        }

        return $this->errorResponse('Failed to approve', 500);
    }

    public function reject(int $id): JsonResponse
    {
        $approval = WorkflowApproval::find($id);

        if (!$approval) {
            return $this->notFoundResponse();
        }

        if (!$approval->canBeActedByUser(Auth::id())) {
            return $this->errorResponse('You cannot reject this request', 403);
        }

        $comments = request()->input('comments');

        if (empty($comments)) {
            return $this->errorResponse('Rejection reason is required', 422);
        }

        if ($this->workflowService->reject($approval, $comments)) {
            return $this->successResponse(
                new WorkflowApprovalResource($approval->fresh(['instance', 'step'])),
                'Rejected successfully'
            );
        }

        return $this->errorResponse('Failed to reject', 500);
    }

    public function delegate(int $id, Request $request): JsonResponse
    {
        $approval = WorkflowApproval::find($id);

        if (!$approval) {
            return $this->notFoundResponse();
        }

        if (!$approval->canBeActedByUser(Auth::id())) {
            return $this->errorResponse('You cannot delegate this request', 403);
        }

        if (!$approval->step->allow_delegation) {
            return $this->errorResponse('Delegation is not allowed for this step', 403);
        }

        $validated = $request->validate([
            'delegate_to' => 'required|integer|exists:users,id',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($this->workflowService->delegate($approval, $validated['delegate_to'], $validated['reason'] ?? null)) {
            return $this->successResponse(
                new WorkflowApprovalResource($approval->fresh(['instance', 'step'])),
                'Delegated successfully'
            );
        }

        return $this->errorResponse('Failed to delegate', 500);
    }

    public function count(): JsonResponse
    {
        $userId = Auth::id();

        $count = WorkflowApproval::pending()
            ->forUser($userId)
            ->count();

        $overdueCount = WorkflowApproval::overdue()
            ->forUser($userId)
            ->count();

        return $this->successResponse([
            'pending' => $count,
            'overdue' => $overdueCount,
        ]);
    }
}
