<?php

namespace App\Http\Requests\Plane;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaneRequest extends FormRequest
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
