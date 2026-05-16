<?php

namespace App\Http\Requests\Party;

use Illuminate\Foundation\Http\FormRequest;

class BulkActivatePartyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_ids' => 'required|array|min:1',
            'party_ids.*' => 'required|integer|exists:parties,id',
            'is_active' => 'required|boolean',
        ];
    }
}
