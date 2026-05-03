<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'         => ['required', 'string', 'max:50', 'unique:parties,code'],
            'type'         => ['required', Rule::in(['individual', 'company'])],
            'display_name' => ['required', 'string', 'max:255'],
            'legal_name'   => ['nullable', 'string', 'max:255'],
            'tax_number'   => ['nullable', 'string', 'max:50'],
            'currency_id'  => ['nullable', 'integer', 'exists:currencies,id'],
            'notes'        => ['nullable', 'string'],
            'is_active'    => ['nullable', 'boolean'],
            'roles'        => ['nullable', 'array'],
            'roles.*'      => [Rule::in(['customer', 'supplier', 'agent', 'contractor'])],
        ];
    }
}
