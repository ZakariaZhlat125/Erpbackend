<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'number' => $this->number,
            'type' => $this->type,
            'party_id' => $this->party_id,
            'status' => $this->status,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'due_date' => $this->due_date?->format('Y-m-d'),
            'currency_code' => $this->currency_code,
            'subtotal' => $this->subtotal,
            'discount_total' => $this->discount_total,
            'tax_total' => $this->tax_total,
            'grand_total' => $this->grand_total,
            'payment_term_id' => $this->payment_term_id,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'approved_at' => $this->approved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Computed attributes
            'is_draft' => $this->isDraft(),
            'is_approved' => $this->isApproved(),
            'is_paid' => $this->isPaid(),
            'is_overdue' => $this->isOverdue(),
            
            // Relationships (when loaded)
            'party' => new PartyResource($this->whenLoaded('party')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'lines' => $this->when($this->relationLoaded('lines'), function () {
                return $this->lines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'product_id' => $line->product_id,
                        'description' => $line->description,
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'discount_amount' => $line->discount_amount,
                        'tax_rate_id' => $line->tax_rate_id,
                        'tax_amount' => $line->tax_amount,
                        'line_total' => $line->line_total,
                        'sort_order' => $line->sort_order,
                    ];
                });
            }),
            'payment_term' => $this->when($this->relationLoaded('paymentTerm'), function () {
                return [
                    'id' => $this->paymentTerm->id,
                    'name' => $this->paymentTerm->name,
                    'due_days' => $this->paymentTerm->due_days,
                ];
            }),
        ];
    }
}
