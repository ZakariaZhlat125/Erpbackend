<?php

namespace App\Http\Requests\Admin\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'sometimes|required|exists:users,id',
            'plan_id' => 'sometimes|required|exists:plans,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'trial_ends_at' => 'nullable|date',
            'status' => 'sometimes|required|in:active,expired,cancelled,trial',
            'auto_renew' => 'boolean',
            'price_paid' => 'sometimes|required|numeric|min:0',
            'billing_cycle' => 'sometimes|required|in:monthly,yearly,lifetime',
            'cancellation_reason' => 'nullable|string|max:500',
        ];
    }
}
