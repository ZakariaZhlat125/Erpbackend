<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Employee\BulkUpdateStatusEmployeeRequest;
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

    public function import(): JsonResponse
    {
        // TODO: Validate Excel file upload
        // TODO: Implement ImportEmployeesAction
        // $file = request()->file('file');
        // $result = app(ImportEmployeesAction::class)->execute($file);

        return $this->successResponse(
            ['message' => 'Import functionality not implemented yet'],
            'Employees import queued'
        );
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->employeeService->getStatistics();

        return $this->successResponse($stats, 'Employee statistics retrieved');
    }

    public function search(): JsonResponse
    {
        $criteria = request()->only([
            'employee_number_like',
            'full_name_like',
            'first_name_like',
            'last_name_like',
            'email',
            'department_name',
            'job_title_like',
            'status',
            'hire_date_from',
            'hire_date_to',
            'base_salary_from',
            'base_salary_to',
        ]);

        $perPage = request()->integer('per_page', 15);
        $results = $this->employeeService->search($criteria, $perPage);

        return $this->paginatedResponse($results);
    }

    public function bulkUpdateStatus(BulkUpdateStatusEmployeeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $count = $this->employeeService->updateMany(
            $validated['employee_ids'],
            ['status' => $validated['status']]
        );

        return $this->successResponse(
            ['updated_count' => $count],
            "Successfully updated status for {$count} employees"
        );
    }

    public function orgChart(): JsonResponse
    {
        $orgChart = $this->employeeService->getOrgChart();

        return $this->successResponse($orgChart, 'Organization chart retrieved');
    }

    public function export(): mixed
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        // $employees = $this->employeeService->getAll();
        // return Excel::download(new EmployeesExport($employees), 'employees.xlsx');

        return $this->errorResponse('Export functionality not implemented yet', 501);
    }
}
