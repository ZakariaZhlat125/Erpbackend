<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class BulkActivateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',
            'is_active' => 'required|boolean',
        ];
    }
}
