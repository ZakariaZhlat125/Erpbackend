<?php

namespace App\Http\Requests\AccountService;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            //
        ];
    }
}
