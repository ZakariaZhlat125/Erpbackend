<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;

class AddPartyRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:customer,supplier,agent,contractor'],
        ];
    }
}
