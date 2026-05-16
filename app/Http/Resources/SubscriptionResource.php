<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ],
            'plan_id' => $this->plan_id,
            'plan' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'price' => $this->plan->price,
                'billing_cycle' => $this->plan->billing_cycle,
            ],
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'trial_ends_at' => $this->trial_ends_at?->format('Y-m-d'),
            'status' => $this->status,
            'auto_renew' => $this->auto_renew,
            'price_paid' => $this->price_paid,
            'billing_cycle' => $this->billing_cycle,
            'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
            'cancellation_reason' => $this->cancellation_reason,
            'is_active' => $this->isActive(),
            'is_expired' => $this->isExpired(),
            'is_cancelled' => $this->isCancelled(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
