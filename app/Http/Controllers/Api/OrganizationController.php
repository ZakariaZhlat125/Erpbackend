<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;

class OrganizationController extends BaseApiController
{
    public function __construct(
        protected OrganizationService $organizationService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->organizationService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->create($request->validated());

        return $this->createdResponse(
            new OrganizationResource($organization)
        );
    }

    public function show(int $id): JsonResponse
    {
        $organization = $this->organizationService->findById($id);

        if (!$organization) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new OrganizationResource($organization)
        );
    }

    public function update(UpdateOrganizationRequest $request, int $id): JsonResponse
    {
        if (!$this->organizationService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->organizationService->update($id, $request->validated());
        $organization = $this->organizationService->findById($id);

        return $this->successResponse(
            new OrganizationResource($organization),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->organizationService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->organizationService->delete($id);

        return $this->noContentResponse();
    }
}
