<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organizations'                        => ['required', 'array', 'min:1'],
            'organizations.*.name'                 => ['required', 'string', 'max:255'],
            'organizations.*.legal_name'           => ['required', 'string', 'max:255'],
            'organizations.*.tax_number'           => ['required', 'string', 'max:50', 'distinct', 'unique:organizations,tax_number'],
            'organizations.*.base_currency_id'     => ['nullable', 'integer', 'exists:currencies,id'],
            'organizations.*.timezone'             => ['nullable', 'string', 'max:50'],
            'organizations.*.locale'               => ['nullable', 'string', 'max:10'],
            'organizations.*.status'               => ['nullable', Rule::in(['active', 'suspended', 'inactive'])],
            'organizations.*.address'              => ['nullable', 'string'],
            'organizations.*.phone'                => ['nullable', 'string', 'max:20'],
            'organizations.*.email'                => ['nullable', 'email', 'max:255'],
            'organizations.*.website'              => ['nullable', 'string', 'max:255'],
            'organizations.*.logo_path'            => ['nullable', 'string', 'max:255'],
        ];
    }
}
