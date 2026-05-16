<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class BulkUpdatePricesProductRequest extends FormRequest
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
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'cost_price_percentage' => 'sometimes|numeric',
            'selling_price_percentage' => 'sometimes|numeric',
        ];
    }
}
