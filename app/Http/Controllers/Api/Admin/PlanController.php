<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Plan\StorePlanRequest;
use App\Http\Requests\Admin\Plan\UpdatePlanRequest;
use App\Http\Resources\PlanResource;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;

class PlanController extends BaseApiController
{
    public function __construct(
        protected PlanService $planService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->planService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

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

    public function togglePopular(int $id): JsonResponse
    {
        if (!$this->planService->exists($id)) {
            return $this->notFoundResponse();
        }

        $plan = $this->planService->findById($id);
        $this->planService->update($id, ['is_popular' => !$plan->is_popular]);
        $plan = $this->planService->findById($id);

        return $this->successResponse(
            new PlanResource($plan),
            'Plan popular status updated successfully'
        );
    }
}
