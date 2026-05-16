<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
        ];
    }
}
