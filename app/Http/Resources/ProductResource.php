<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'sku' => $this->sku,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id,
            'unit_id' => $this->unit_id,
            'cost_price' => $this->cost_price,
            'selling_price' => $this->selling_price,
            'tax_rate_id' => $this->tax_rate_id,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'image_path' => $this->image_path,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Computed attributes
            'total_stock' => $this->when($this->track_inventory, function () {
                return $this->total_stock ?? null;
            }),
            
            // Relationships (when loaded)
            'category' => $this->when($this->relationLoaded('category'), function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                ];
            }),
            'unit' => $this->when($this->relationLoaded('unit'), function () {
                return [
                    'id' => $this->unit->id,
                    'name' => $this->unit->name,
                    'symbol' => $this->unit->symbol,
                ];
            }),
            'tax_rate' => $this->when($this->relationLoaded('taxRate'), function () {
                return [
                    'id' => $this->taxRate->id,
                    'name' => $this->taxRate->name,
                    'rate' => $this->taxRate->rate,
                ];
            }),
        ];
    }
}
