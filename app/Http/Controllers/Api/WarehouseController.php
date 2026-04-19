<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;

class WarehouseController extends BaseApiController
{
    public function __construct(
        protected WarehouseService $warehouseService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->warehouseService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $warehouse = $this->warehouseService->create($request->validated());

        return $this->createdResponse(
            new WarehouseResource($warehouse)
        );
    }

    public function show(int $id): JsonResponse
    {
        $warehouse = $this->warehouseService->findById($id);

        if (!$warehouse) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new WarehouseResource($warehouse)
        );
    }

    public function update(UpdateWarehouseRequest $request, int $id): JsonResponse
    {
        if (!$this->warehouseService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->warehouseService->update($id, $request->validated());
        $warehouse = $this->warehouseService->findById($id);

        return $this->successResponse(
            new WarehouseResource($warehouse),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->warehouseService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->warehouseService->delete($id);

        return $this->noContentResponse();
    }
}
