<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\ActivityLogResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends BaseApiController
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    public function index(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        
        $filters = request()->only([
            'actor_id',
            'action',
            'subject_type',
            'subject_id',
            'from',
            'to',
            'sensitive_only',
            'search',
        ]);

        $data = $this->activityLogService->getForOrganization(
            $organizationId,
            $filters,
            request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data, ActivityLogResource::class);
    }

    public function show(int $id): JsonResponse
    {
        $activityLog = $this->activityLogService->findById($id, ['*'], ['actor', 'subject']);

        if (!$activityLog) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new ActivityLogResource($activityLog)
        );
    }

    public function sensitive(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;

        $data = $this->activityLogService->getSensitiveLogs(
            $organizationId,
            request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data, ActivityLogResource::class);
    }

    public function bySubject(string $subjectType, int $subjectId): JsonResponse
    {
        $data = $this->activityLogService->getBySubject(
            $subjectType,
            $subjectId,
            request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data, ActivityLogResource::class);
    }

    public function byActor(int $actorId): JsonResponse
    {
        $data = $this->activityLogService->getByActor(
            $actorId,
            request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data, ActivityLogResource::class);
    }

    public function statistics(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        $from = request()->query('from');
        $to = request()->query('to');

        $stats = $this->activityLogService->getStatistics($organizationId, $from, $to);

        return $this->successResponse($stats, 'Activity log statistics retrieved');
    }

    public function actions(): JsonResponse
    {
        $actions = $this->activityLogService->getAvailableActions();

        return $this->successResponse($actions, 'Available actions retrieved');
    }

    public function subjectTypes(): JsonResponse
    {
        $types = $this->activityLogService->getAvailableSubjectTypes();

        return $this->successResponse($types, 'Available subject types retrieved');
    }
}
