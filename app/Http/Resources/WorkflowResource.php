<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'description' => $this->description,
            'entity_type' => $this->entity_type,
            'entity_type_short' => class_basename($this->entity_type),
            'trigger_on' => $this->trigger_on,
            'trigger_field' => $this->trigger_field,
            'trigger_value' => $this->trigger_value,
            'is_active' => $this->is_active,
            'allow_parallel' => $this->allow_parallel,
            'config' => $this->config_json,
            'total_steps' => $this->whenLoaded('steps', fn() => $this->steps->count()),
            'steps' => WorkflowStepResource::collection($this->whenLoaded('steps')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
