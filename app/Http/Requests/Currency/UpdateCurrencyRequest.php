<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'sometimes|string|size:3|unique:currencies,code,' . $this->route('id'),
            'name' => 'sometimes|string|max:255',
            'symbol' => 'sometimes|string|max:10',
            'decimal_separator' => 'nullable|string|size:1',
            'thousands_separator' => 'nullable|string|size:1',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}
