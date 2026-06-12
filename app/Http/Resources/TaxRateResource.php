<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'rate' => $this->rate,
            'display_rate' => $this->getDisplayRate(),
            'type' => $this->type,
            'calculation' => $this->calculation,
            'sales_account_id' => $this->sales_account_id,
            'purchase_account_id' => $this->purchase_account_id,
            'is_compound' => $this->is_compound,
            'is_inclusive' => $this->is_inclusive,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'effective_from' => $this->effective_from?->format('Y-m-d'),
            'effective_to' => $this->effective_to?->format('Y-m-d'),
            'is_effective' => $this->isEffective(),

            'sales_account' => $this->when($this->relationLoaded('salesAccount') && $this->salesAccount, fn() => [
                'id' => $this->salesAccount->id,
                'code' => $this->salesAccount->code,
                'name' => $this->salesAccount->name,
            ]),
            'purchase_account' => $this->when($this->relationLoaded('purchaseAccount') && $this->purchaseAccount, fn() => [
                'id' => $this->purchaseAccount->id,
                'code' => $this->purchaseAccount->code,
                'name' => $this->purchaseAccount->name,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
