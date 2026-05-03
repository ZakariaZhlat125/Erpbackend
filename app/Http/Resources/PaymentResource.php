<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'party_id' => $this->party_id,
            'invoice_id' => $this->invoice_id,
            'number' => $this->number,
            'direction' => $this->direction,
            'method' => $this->method,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'paid_at' => $this->paid_at?->format('Y-m-d H:i:s'),
            'reference' => $this->reference,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            
            // Relationships (when loaded)
            'party' => new PartyResource($this->whenLoaded('party')),
            'invoice' => new InvoiceResource($this->whenLoaded('invoice')),
            'allocations' => $this->when($this->relationLoaded('allocations'), function () {
                return $this->allocations->map(function ($allocation) {
                    return [
                        'id' => $allocation->id,
                        'invoice_id' => $allocation->invoice_id,
                        'allocated_amount' => $allocation->allocated_amount,
                    ];
                });
            }),
        ];
    }
}
