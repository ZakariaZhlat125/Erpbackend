<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdateStatusEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'required|integer|exists:employees,id',
            'status' => 'required|in:active,inactive,terminated',
        ];
    }
}
