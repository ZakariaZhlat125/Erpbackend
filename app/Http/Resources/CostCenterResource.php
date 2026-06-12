<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CostCenterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'parent_id' => $this->parent_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'level' => $this->level,
            'path' => $this->path,
            'budget' => $this->budget,
            'is_root' => $this->isRoot(),
            'has_children' => $this->hasChildren(),

            'parent' => $this->when($this->relationLoaded('parent') && $this->parent, fn() => [
                'id' => $this->parent->id,
                'code' => $this->parent->code,
                'name' => $this->parent->name,
            ]),
            'children' => CostCenterResource::collection($this->whenLoaded('children')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
