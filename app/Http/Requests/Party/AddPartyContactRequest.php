<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;

class AddPartyContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }
}
