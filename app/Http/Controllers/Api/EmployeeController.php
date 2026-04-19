<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Employee\StoreEmployeeRequest;
use App\Http\Requests\Employee\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Services\EmployeeService;
use Illuminate\Http\JsonResponse;

class EmployeeController extends BaseApiController
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->employeeService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->validated());

        return $this->createdResponse(
            new EmployeeResource($employee)
        );
    }

    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->findById($id);

        if (!$employee) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new EmployeeResource($employee)
        );
    }

    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        if (!$this->employeeService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->employeeService->update($id, $request->validated());
        $employee = $this->employeeService->findById($id);

        return $this->successResponse(
            new EmployeeResource($employee),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->employeeService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->employeeService->delete($id);

        return $this->noContentResponse();
    }
}
