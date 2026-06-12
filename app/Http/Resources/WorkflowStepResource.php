<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkflowStepResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workflow_id' => $this->workflow_id,
            'order' => $this->order,
            'name' => $this->name,
            'description' => $this->description,
            'approver_type' => $this->approver_type,
            'approver_value' => $this->approver_value,
            'condition' => $this->condition_json,
            'timeout_hours' => $this->timeout_hours,
            'on_timeout' => $this->on_timeout,
            'escalate_to' => $this->escalate_to,
            'on_reject' => $this->on_reject,
            'goto_step_id' => $this->goto_step_id,
            'is_optional' => $this->is_optional,
            'allow_delegation' => $this->allow_delegation,
            'notifications' => $this->notifications_json,
            'is_first' => $this->isFirstStep(),
            'is_last' => $this->isLastStep(),
        ];
    }
}
