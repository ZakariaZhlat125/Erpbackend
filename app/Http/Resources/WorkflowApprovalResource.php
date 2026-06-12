<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'instance_id' => $this->instance_id,
            'step_id' => $this->step_id,
            'assigned_to' => $this->assigned_to,
            'acted_by' => $this->acted_by,
            'status' => $this->status,
            'comments' => $this->comments,
            'delegated_to' => $this->delegated_to,
            'delegation_reason' => $this->delegation_reason,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'acted_at' => $this->acted_at?->toISOString(),
            'due_at' => $this->due_at?->toISOString(),
            'is_overdue' => $this->isOverdue(),
            'remaining_minutes' => $this->getRemainingTime(),
            'meta' => $this->meta_json,

            // Relationships
            'step' => new WorkflowStepResource($this->whenLoaded('step')),
            'assigned_user' => $this->when($this->relationLoaded('assignedTo'), fn() => [
                'id' => $this->assignedTo?->id,
                'name' => $this->assignedTo?->name,
                'email' => $this->assignedTo?->email,
            ]),
            'acted_by_user' => $this->when($this->relationLoaded('actedBy'), fn() => [
                'id' => $this->actedBy?->id,
                'name' => $this->actedBy?->name,
                'email' => $this->actedBy?->email,
            ]),
            'instance' => new WorkflowInstanceResource($this->whenLoaded('instance')),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
