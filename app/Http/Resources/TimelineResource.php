<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimelineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'type' => $this->resource['type'],
            'action' => $this->resource['action'],
            'icon' => $this->resource['icon'],
            'color' => $this->resource['color'],
            'title' => $this->resource['title'],
            'description' => $this->resource['description'] ?? null,
            'user' => $this->resource['user'] ?? null,
            'timestamp' => $this->resource['timestamp']?->toISOString(),
            'timestamp_human' => $this->resource['timestamp']?->diffForHumans(),
            
            // Type-specific data
            'changes' => $this->resource['changes'] ?? null,
            'meta' => $this->resource['meta'] ?? null,
            'file' => $this->resource['file'] ?? null,
            'payment' => $this->resource['payment'] ?? null,
            'replies' => $this->resource['replies'] ?? null,
            'replies_count' => $this->resource['replies_count'] ?? null,
            'step_name' => $this->resource['step_name'] ?? null,
            'is_internal' => $this->resource['is_internal'] ?? null,
            'is_pinned' => $this->resource['is_pinned'] ?? null,
            'ip_address' => $this->resource['ip_address'] ?? null,
        ];
    }
}
