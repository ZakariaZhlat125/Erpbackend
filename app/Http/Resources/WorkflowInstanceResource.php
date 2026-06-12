<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowInstanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'organization_id' => $this->organization_id,
            'entity_type' => $this->entity_type,
            'entity_type_short' => class_basename($this->entity_type),
            'entity_id' => $this->entity_id,
            'current_step_id' => $this->current_step_id,
            'status' => $this->status,
            'initiated_by' => $this->initiated_by,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'completed_by' => $this->completed_by,
            'final_comments' => $this->final_comments,
            'meta' => $this->meta_json,
            'progress' => $this->getProgress(),
            
            // Relationships
            'workflow' => new WorkflowResource($this->whenLoaded('workflow')),
            'current_step' => new WorkflowStepResource($this->whenLoaded('currentStep')),
            'initiator' => $this->when($this->relationLoaded('initiator'), fn() => [
                'id' => $this->initiator?->id,
                'name' => $this->initiator?->name,
                'email' => $this->initiator?->email,
            ]),
            'entity' => $this->when($this->relationLoaded('entity'), fn() => $this->formatEntity()),
            'approvals' => WorkflowApprovalResource::collection($this->whenLoaded('approvals')),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function formatEntity(): ?array
    {
        if (!$this->entity) {
            return null;
        }

        return [
            'id' => $this->entity->id,
            'type' => class_basename($this->entity),
            'name' => $this->entity->name ?? $this->entity->title ?? $this->entity->number ?? "#{$this->entity->id}",
        ];
    }
}
