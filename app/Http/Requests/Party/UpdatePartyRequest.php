<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('party');

        return [
            'code'         => ['sometimes', 'required', 'string', 'max:50', Rule::unique('parties', 'code')->ignore($id)],
            'type'         => ['sometimes', 'required', Rule::in(['individual', 'company'])],
            'display_name' => ['sometimes', 'required', 'string', 'max:255'],
            'legal_name'   => ['nullable', 'string', 'max:255'],
            'tax_number'   => ['nullable', 'string', 'max:50'],
            'currency_id'  => ['nullable', 'integer', 'exists:currencies,id'],
            'notes'        => ['nullable', 'string'],
            'is_active'    => ['nullable', 'boolean'],
        ];
    }
}
