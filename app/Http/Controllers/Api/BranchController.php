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

    public function index(): JsonResponse
    {
        $data = $this->branchService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = $this->branchService->create($request->validated());

        return $this->createdResponse(
            new BranchResource($branch)
        );
    }

    public function show(int $id): JsonResponse
    {
        $branch = $this->branchService->findById($id);

        if (!$branch) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new BranchResource($branch)
        );
    }

    public function update(UpdateBranchRequest $request, int $id): JsonResponse
    {
        if (!$this->branchService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->branchService->update($id, $request->validated());
        $branch = $this->branchService->findById($id);

        return $this->successResponse(
            new BranchResource($branch),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->branchService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->branchService->delete($id);

        return $this->noContentResponse();
    }
}
