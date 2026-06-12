<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'number' => $this->number,
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id' => $this->to_warehouse_id,
            'status' => $this->status,
            'request_date' => $this->request_date?->format('Y-m-d'),
            'expected_date' => $this->expected_date?->format('Y-m-d'),
            'shipped_date' => $this->shipped_date?->format('Y-m-d'),
            'received_date' => $this->received_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'shipping_notes' => $this->shipping_notes,
            'total_quantity' => $this->getTotalQuantity(),
            'can_be_edited' => $this->canBeEdited(),
            'can_be_approved' => $this->canBeApproved(),
            'can_be_shipped' => $this->canBeShipped(),
            'can_be_received' => $this->canBeReceived(),

            'from_warehouse' => $this->when($this->relationLoaded('fromWarehouse'), fn() => [
                'id' => $this->fromWarehouse->id,
                'name' => $this->fromWarehouse->name,
            ]),
            'to_warehouse' => $this->when($this->relationLoaded('toWarehouse'), fn() => [
                'id' => $this->toWarehouse->id,
                'name' => $this->toWarehouse->name,
            ]),
            'requested_by' => $this->when($this->relationLoaded('requestedBy'), fn() => [
                'id' => $this->requestedBy?->id,
                'name' => $this->requestedBy?->name,
            ]),
            'approved_by' => $this->when($this->approved_by, fn() => [
                'id' => $this->approvedBy?->id,
                'name' => $this->approvedBy?->name,
            ]),
            'approved_at' => $this->approved_at?->toISOString(),

            'lines' => StockTransferLineResource::collection($this->whenLoaded('lines')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
