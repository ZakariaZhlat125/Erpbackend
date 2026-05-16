<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Plane\StorePlaneRequest;
use App\Http\Requests\Plane\UpdatePlaneRequest;
use App\Http\Resources\PlaneResource;
use App\Services\PlaneService;
use Illuminate\Http\JsonResponse;

class PlaneController extends BaseApiController
{
    public function __construct(
        protected PlaneService $planeService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->planeService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StorePlaneRequest $request): JsonResponse
    {
        $plane = $this->planeService->create($request->validated());

        return $this->createdResponse(
            new PlaneResource($plane)
        );
    }

    public function show(int $id): JsonResponse
    {
        $plane = $this->planeService->findById($id);

        if (!$plane) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PlaneResource($plane)
        );
    }

    public function update(UpdatePlaneRequest $request, int $id): JsonResponse
    {
        if (!$this->planeService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->planeService->update($id, $request->validated());
        $plane = $this->planeService->findById($id);

        return $this->successResponse(
            new PlaneResource($plane),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->planeService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->planeService->delete($id);

        return $this->noContentResponse();
    }
}
