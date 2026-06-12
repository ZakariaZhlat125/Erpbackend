<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalBatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'number' => $this->number,
            'date' => $this->date?->format('Y-m-d'),
            'reference' => $this->reference,
            'description' => $this->description,
            'type' => $this->type,
            'status' => $this->status,
            'source_type' => $this->source_type ? class_basename($this->source_type) : null,
            'source_id' => $this->source_id,
            'currency_code' => $this->currency_code,
            'exchange_rate' => $this->exchange_rate,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'is_balanced' => $this->isBalanced(),
            'can_be_edited' => $this->canBeEdited(),
            'can_be_posted' => $this->canBePosted(),
            'can_be_voided' => $this->canBeVoided(),
            
            'created_by' => $this->when($this->relationLoaded('createdBy'), fn() => [
                'id' => $this->createdBy?->id,
                'name' => $this->createdBy?->name,
            ]),
            'posted_by' => $this->when($this->posted_by, fn() => [
                'id' => $this->postedBy?->id,
                'name' => $this->postedBy?->name,
            ]),
            'posted_at' => $this->posted_at?->toISOString(),
            'voided_by' => $this->when($this->voided_by, fn() => [
                'id' => $this->voidedBy?->id,
                'name' => $this->voidedBy?->name,
            ]),
            'voided_at' => $this->voided_at?->toISOString(),
            'void_reason' => $this->void_reason,

            'lines' => JournalLineResource::collection($this->whenLoaded('lines')),
            'lines_count' => $this->whenLoaded('lines', fn() => $this->lines->count()),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
