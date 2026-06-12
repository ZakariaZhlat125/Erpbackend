<?php

namespace App\Services\WorkflowEngine;

use Illuminate\Database\Eloquent\Model;

class ConditionEvaluator
{
    protected array $operators = [
        '=' => 'evaluateEquals',
        '==' => 'evaluateEquals',
        '!=' => 'evaluateNotEquals',
        '<>' => 'evaluateNotEquals',
        '>' => 'evaluateGreaterThan',
        '>=' => 'evaluateGreaterThanOrEqual',
        '<' => 'evaluateLessThan',
        '<=' => 'evaluateLessThanOrEqual',
        'in' => 'evaluateIn',
        'not_in' => 'evaluateNotIn',
        'contains' => 'evaluateContains',
        'not_contains' => 'evaluateNotContains',
        'starts_with' => 'evaluateStartsWith',
        'ends_with' => 'evaluateEndsWith',
        'is_null' => 'evaluateIsNull',
        'is_not_null' => 'evaluateIsNotNull',
        'is_empty' => 'evaluateIsEmpty',
        'is_not_empty' => 'evaluateIsNotEmpty',
        'between' => 'evaluateBetween',
    ];

    public function evaluate(Model $entity, ?array $condition): bool
    {
        if (empty($condition)) {
            return true;
        }

        // Handle grouped conditions (AND/OR)
        if (isset($condition['type']) && in_array($condition['type'], ['and', 'or'])) {
            return $this->evaluateGroup($entity, $condition);
        }

        // Single condition
        return $this->evaluateSingle($entity, $condition);
    }

    protected function evaluateGroup(Model $entity, array $group): bool
    {
        $type = $group['type'] ?? 'and';
        $conditions = $group['conditions'] ?? [];

        if (empty($conditions)) {
            return true;
        }

        if ($type === 'or') {
            foreach ($conditions as $condition) {
                if ($this->evaluate($entity, $condition)) {
                    return true;
                }
            }
            return false;
        }

        // AND logic (default)
        foreach ($conditions as $condition) {
            if (!$this->evaluate($entity, $condition)) {
                return false;
            }
        }
        return true;
    }

    protected function evaluateSingle(Model $entity, array $condition): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? null;

        if (!$field) {
            return true;
        }

        $entityValue = $this->getFieldValue($entity, $field);
        $method = $this->operators[$operator] ?? 'evaluateEquals';

        return $this->$method($entityValue, $value);
    }

    protected function getFieldValue(Model $entity, string $field): mixed
    {
        // Support nested fields like "party.type" or "created_by.department_id"
        if (str_contains($field, '.')) {
            $parts = explode('.', $field);
            $value = $entity;

            foreach ($parts as $part) {
                if ($value === null) {
                    return null;
                }
                $value = $value->$part ?? null;
            }

            return $value;
        }

        return $entity->$field ?? null;
    }

    // ==================== Operators ====================
    protected function evaluateEquals(mixed $entityValue, mixed $value): bool
    {
        return $entityValue == $value;
    }

    protected function evaluateNotEquals(mixed $entityValue, mixed $value): bool
    {
        return $entityValue != $value;
    }

    protected function evaluateGreaterThan(mixed $entityValue, mixed $value): bool
    {
        return is_numeric($entityValue) && is_numeric($value) && $entityValue > $value;
    }

    protected function evaluateGreaterThanOrEqual(mixed $entityValue, mixed $value): bool
    {
        return is_numeric($entityValue) && is_numeric($value) && $entityValue >= $value;
    }

    protected function evaluateLessThan(mixed $entityValue, mixed $value): bool
    {
        return is_numeric($entityValue) && is_numeric($value) && $entityValue < $value;
    }

    protected function evaluateLessThanOrEqual(mixed $entityValue, mixed $value): bool
    {
        return is_numeric($entityValue) && is_numeric($value) && $entityValue <= $value;
    }

    protected function evaluateIn(mixed $entityValue, mixed $value): bool
    {
        $array = is_array($value) ? $value : explode(',', (string) $value);
        return in_array($entityValue, $array);
    }

    protected function evaluateNotIn(mixed $entityValue, mixed $value): bool
    {
        return !$this->evaluateIn($entityValue, $value);
    }

    protected function evaluateContains(mixed $entityValue, mixed $value): bool
    {
        return is_string($entityValue) && str_contains($entityValue, (string) $value);
    }

    protected function evaluateNotContains(mixed $entityValue, mixed $value): bool
    {
        return !$this->evaluateContains($entityValue, $value);
    }

    protected function evaluateStartsWith(mixed $entityValue, mixed $value): bool
    {
        return is_string($entityValue) && str_starts_with($entityValue, (string) $value);
    }

    protected function evaluateEndsWith(mixed $entityValue, mixed $value): bool
    {
        return is_string($entityValue) && str_ends_with($entityValue, (string) $value);
    }

    protected function evaluateIsNull(mixed $entityValue, mixed $value): bool
    {
        return $entityValue === null;
    }

    protected function evaluateIsNotNull(mixed $entityValue, mixed $value): bool
    {
        return $entityValue !== null;
    }

    protected function evaluateIsEmpty(mixed $entityValue, mixed $value): bool
    {
        return empty($entityValue);
    }

    protected function evaluateIsNotEmpty(mixed $entityValue, mixed $value): bool
    {
        return !empty($entityValue);
    }

    protected function evaluateBetween(mixed $entityValue, mixed $value): bool
    {
        if (!is_array($value) || count($value) !== 2) {
            return false;
        }

        [$min, $max] = $value;
        return is_numeric($entityValue) && $entityValue >= $min && $entityValue <= $max;
    }
}
