<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\PlanResource;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;

class PlanController extends BaseApiController
{
    public function __construct(
        protected PlanService $planService
    ) {}

    /**
     * Get available plans for organization users
     * Returns only active plans that can be subscribed to
     */
    public function availablePlans(): JsonResponse
    {
        $plans = $this->planService->getActivePlans();

        return $this->successResponse([
            'data' => PlanResource::collection($plans),
        ]);
    }

    /**
     * Get single plan details
     */
    public function show(int $id): JsonResponse
    {
        $plan = $this->planService->findById($id);

        if (!$plan || !$plan->is_active) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PlanResource($plan)
        );
    }
}
