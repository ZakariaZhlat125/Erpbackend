<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'legal_name'       => ['required', 'string', 'max:255'],
            'tax_number'       => ['required', 'string', 'max:50', 'unique:organizations,tax_number'],
            'base_currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'timezone'         => ['nullable', 'string', 'max:50'],
            'locale'           => ['nullable', 'string', 'max:10'],
            'status'           => ['nullable', Rule::in(['active', 'suspended', 'inactive'])],
            'address'          => ['nullable', 'string'],
            'phone'            => ['nullable', 'string', 'max:20'],
            'email'            => ['nullable', 'email', 'max:255'],
            'website'          => ['nullable', 'string', 'max:255'],
            'logo_path'        => ['nullable', 'string', 'max:255'],
        ];
    }
}
