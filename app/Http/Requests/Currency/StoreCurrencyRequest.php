<?php

namespace App\Http\Requests\Currency;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|size:3|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'decimal_separator' => 'nullable|string|size:1',
            'thousands_separator' => 'nullable|string|size:1',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ];
    }
}
