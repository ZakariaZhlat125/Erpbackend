<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Http\Resources\BranchResource;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;

class BranchController extends BaseApiController
{
    public function __construct(
        protected BranchService $branchService
    ) {}

    public function index(int $organization): JsonResponse
    {
        $data = $this->branchService->allByOrganization(
            organizationId: $organization,
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreBranchRequest $request, int $organization): JsonResponse
    {
        $branch = $this->branchService->createForOrganization($organization, $request->validated());

        return $this->createdResponse(new BranchResource($branch));
    }

    public function show(int $organization, int $branch): JsonResponse
    {
        $model = $this->branchService->findByIdAndOrganization($branch, $organization);

        if (! $model) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new BranchResource($model));
    }

    public function update(UpdateBranchRequest $request, int $organization, int $branch): JsonResponse
    {
        $model = $this->branchService->updateForOrganization($branch, $organization, $request->validated());

        if (! $model) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new BranchResource($model), 'Branch updated successfully');
    }

    public function destroy(int $organization, int $branch): JsonResponse
    {
        $deleted = $this->branchService->deleteForOrganization($branch, $organization);

        if (! $deleted) {
            return $this->notFoundResponse();
        }

        return $this->noContentResponse();
    }

    public function toggleStatus(int $organization, int $branch): JsonResponse
    {
        $model = $this->branchService->findByIdAndOrganization($branch, $organization);
        if (! $model) {
            return $this->notFoundResponse();
        }

        $model = $this->branchService->toggleStatus($model->id);

        $message = $model->is_active ? 'Branch activated successfully' : 'Branch deactivated successfully';

        return $this->successResponse(new BranchResource($model), $message);
    }
}
