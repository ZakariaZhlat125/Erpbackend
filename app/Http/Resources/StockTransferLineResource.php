<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'transfer_id' => $this->transfer_id,
            'product_id' => $this->product_id,
            'batch_id' => $this->batch_id,
            'quantity_requested' => $this->quantity_requested,
            'quantity_shipped' => $this->quantity_shipped,
            'quantity_received' => $this->quantity_received,
            'unit_cost' => $this->unit_cost,
            'variance' => $this->getVariance(),
            'is_fully_received' => $this->isFullyReceived(),
            'notes' => $this->notes,

            'product' => $this->when($this->relationLoaded('product'), fn() => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'sku' => $this->product->sku,
            ]),
            'batch' => $this->when($this->relationLoaded('batch') && $this->batch, fn() => [
                'id' => $this->batch->id,
                'batch_number' => $this->batch->batch_number,
                'expiry_date' => $this->batch->expiry_date?->format('Y-m-d'),
            ]),
        ];
    }
}
