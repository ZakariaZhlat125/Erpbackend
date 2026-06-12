<?php

namespace App\Services\WorkflowEngine;

use App\Models\User;
use App\Models\WorkflowStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

class ApproverResolver
{
    public function resolve(WorkflowStep $step, Model $entity): Collection
    {
        return match ($step->approver_type) {
            WorkflowStep::APPROVER_USER => $this->resolveUser($step->approver_value),
            WorkflowStep::APPROVER_ROLE => $this->resolveRole($step->approver_value, $entity),
            WorkflowStep::APPROVER_FIELD => $this->resolveField($step->approver_value, $entity),
            WorkflowStep::APPROVER_HIERARCHY => $this->resolveHierarchy($step->approver_value, $entity),
            WorkflowStep::APPROVER_ANY_OF => $this->resolveAnyOf($step->approver_value, $entity),
            WorkflowStep::APPROVER_ALL_OF => $this->resolveAllOf($step->approver_value, $entity),
            default => collect(),
        };
    }

    protected function resolveUser(string $value): Collection
    {
        // Can be single ID or comma-separated IDs
        $userIds = array_map('intval', explode(',', $value));
        
        return User::whereIn('id', $userIds)
            ->where('is_active', true)
            ->get();
    }

    protected function resolveRole(string $value, Model $entity): Collection
    {
        $organizationId = $entity->organization_id ?? null;

        $query = User::role($value, 'sanctum')
            ->where('is_active', true);

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return $query->get();
    }

    protected function resolveField(string $value, Model $entity): Collection
    {
        // Field path like "created_by" or "party.sales_rep_id"
        $userId = $this->getNestedValue($entity, $value);

        if (!$userId) {
            return collect();
        }

        return User::where('id', $userId)
            ->where('is_active', true)
            ->get();
    }

    protected function resolveHierarchy(string $value, Model $entity): Collection
    {
        // Format: "field.relation" like "created_by.manager" or "created_by.department.head"
        $parts = explode('.', $value);
        
        if (count($parts) < 2) {
            return collect();
        }

        // Get the base user
        $baseField = array_shift($parts);
        $baseUserId = $this->getNestedValue($entity, $baseField);
        
        if (!$baseUserId) {
            return collect();
        }

        $baseUser = User::find($baseUserId);
        if (!$baseUser) {
            return collect();
        }

        // Navigate through hierarchy
        $current = $baseUser;
        foreach ($parts as $relation) {
            if (!$current) {
                return collect();
            }

            // Handle common hierarchy relations
            $current = match ($relation) {
                'manager' => $this->getManager($current),
                'supervisor' => $this->getSupervisor($current),
                'department_head' => $this->getDepartmentHead($current),
                'branch_manager' => $this->getBranchManager($current),
                default => $current->$relation ?? null,
            };
        }

        if ($current instanceof User && $current->is_active) {
            return collect([$current]);
        }

        return collect();
    }

    protected function resolveAnyOf(string $value, Model $entity): Collection
    {
        // JSON format: [{"type": "role", "value": "Manager"}, {"type": "user", "value": "5"}]
        $configs = json_decode($value, true);
        
        if (!is_array($configs)) {
            return collect();
        }

        $users = collect();
        foreach ($configs as $config) {
            $step = new WorkflowStep([
                'approver_type' => $config['type'] ?? 'user',
                'approver_value' => $config['value'] ?? '',
            ]);
            $users = $users->merge($this->resolve($step, $entity));
        }

        return $users->unique('id');
    }

    protected function resolveAllOf(string $value, Model $entity): Collection
    {
        // Same as any_of but all must approve
        return $this->resolveAnyOf($value, $entity);
    }

    // ==================== Hierarchy Helpers ====================
    protected function getManager(User $user): ?User
    {
        // Check if user has manager_id field
        if (isset($user->manager_id) && $user->manager_id) {
            return User::find($user->manager_id);
        }

        // Check through employee relation
        if ($user->employee && $user->employee->manager_id) {
            $manager = \App\Models\Employee::find($user->employee->manager_id);
            return $manager?->user;
        }

        return null;
    }

    protected function getSupervisor(User $user): ?User
    {
        // Similar to manager, might be different in some organizations
        return $this->getManager($user);
    }

    protected function getDepartmentHead(User $user): ?User
    {
        if (!$user->employee || !$user->employee->department_id) {
            return null;
        }

        // Find department head
        $department = \App\Models\Department::find($user->employee->department_id);
        if ($department && $department->head_id) {
            return User::find($department->head_id);
        }

        return null;
    }

    protected function getBranchManager(User $user): ?User
    {
        $branchId = $user->branch_id ?? ($user->employee?->branch_id);
        
        if (!$branchId) {
            return null;
        }

        $branch = \App\Models\Branch::find($branchId);
        if ($branch && $branch->manager_id) {
            return User::find($branch->manager_id);
        }

        return null;
    }

    // ==================== Utility ====================
    protected function getNestedValue(Model $entity, string $path): mixed
    {
        $parts = explode('.', $path);
        $value = $entity;

        foreach ($parts as $part) {
            if ($value === null) {
                return null;
            }

            if ($value instanceof Model) {
                $value = $value->$part ?? null;
            } elseif (is_array($value)) {
                $value = $value[$part] ?? null;
            } else {
                return null;
            }
        }

        return $value;
    }
}
