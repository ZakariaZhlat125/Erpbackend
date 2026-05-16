<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Plan\StorePlanRequest;
use App\Http\Requests\Admin\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class PlanController extends BaseApiController
{
    public function __construct(
        protected PlanService $planService
    ) {}

    #[OA\Get(
        path: '/api/admin/plans',
        summary: 'Get all plans',
        tags: ['Admin/Plans'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function index(): JsonResponse
    {
        $data = $this->planService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    #[OA\Post(
        path: '/api/admin/plans',
        summary: 'Create a new plan',
        tags: ['Admin/Plans'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StorePlanRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Plan created'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StorePlanRequest $request): JsonResponse
    {
        $plan = $this->planService->create($request->validated());

        return $this->createdResponse(
            new PlanResource($plan)
        );
    }

    public function show(int $id): JsonResponse
    {
        $plan = $this->planService->findById($id);

        if (!$plan) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PlanResource($plan)
        );
    }

    public function update(UpdatePlanRequest $request, int $id): JsonResponse
    {
        if (!$this->planService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->planService->update($id, $request->validated());
        $plan = $this->planService->findById($id);

        return $this->successResponse(
            new PlanResource($plan),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->planService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->planService->delete($id);

        return $this->noContentResponse();
    }

    #[OA\Patch(
        path: '/api/admin/plans/{id}/change-status',
        summary: 'Toggle plan active status',
        tags: ['Admin/Plans'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status updated'),
            new OA\Response(response: 404, description: 'Plan not found')
        ]
    )]
    public function changeStatus(int $id): JsonResponse
    {
        if (!$this->planService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->planService->changeStatus($id);
        $plan = $this->planService->findById($id);

        return $this->successResponse(
            new PlanResource($plan),
            'Plan status updated successfully'
        );
    }
}
