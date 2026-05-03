<?php

namespace App\Services;

use App\Models\Employee;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EmployeeService extends BaseService
{
    public function __construct(EmployeeRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getStatistics(): array
    {
        $query = Employee::query();

        $stats = [
            'total_count' => (clone $query)->count(),
            'active_count' => (clone $query)->where('status', 'active')->count(),
            'inactive_count' => (clone $query)->where('status', 'inactive')->count(),
            'terminated_count' => (clone $query)->where('status', 'terminated')->count(),
            'avg_salary' => (clone $query)->where('status', 'active')->avg('base_salary'),
            'total_payroll' => (clone $query)->where('status', 'active')->sum('base_salary'),
        ];

        $byDepartment = Employee::select('department_name', DB::raw('COUNT(*) as count'))
            ->whereNotNull('department_name')
            ->where('status', 'active')
            ->groupBy('department_name')
            ->get()
            ->map(function ($item) {
                return [
                    'department' => $item->department_name,
                    'count' => $item->count,
                ];
            });

        $stats['by_department'] = $byDepartment;

        $recentHires = (clone $query)
            ->where('hire_date', '>=', now()->subMonths(3))
            ->orderBy('hire_date', 'desc')
            ->limit(10)
            ->get(['id', 'employee_number', 'full_name', 'hire_date', 'job_title']);

        $stats['recent_hires'] = $recentHires;

        return $stats;
    }

    public function getOrgChart(): array
    {
        $employees = Employee::with('manager:id,employee_number,full_name,job_title')
            ->where('status', 'active')
            ->orderBy('manager_employee_id')
            ->get(['id', 'employee_number', 'full_name', 'job_title', 'manager_employee_id']);

        $buildTree = function ($employees, $parentId = null) use (&$buildTree) {
            $branch = [];

            foreach ($employees as $employee) {
                if ($employee->manager_employee_id == $parentId) {
                    $children = $buildTree($employees, $employee->id);
                    $node = [
                        'id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'name' => $employee->full_name,
                        'job_title' => $employee->job_title,
                    ];
                    if (!empty($children)) {
                        $node['children'] = $children;
                    }
                    $branch[] = $node;
                }
            }

            return $branch;
        };

        return $buildTree($employees);
    }
}
