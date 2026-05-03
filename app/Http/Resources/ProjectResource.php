<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'branch_id' => $this->branch_id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'party_id' => $this->party_id,
            'status' => $this->status,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'budget_amount' => $this->budget_amount,
            'progress_percent' => $this->progress_percent,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            
            // Relationships (when loaded)
            'party' => new PartyResource($this->whenLoaded('party')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'members' => $this->when($this->relationLoaded('members'), function () {
                return $this->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'user_id' => $member->user_id,
                        'role' => $member->role,
                        'hourly_rate' => $member->hourly_rate,
                        'user' => [
                            'id' => $member->user->id,
                            'name' => $member->user->name,
                            'email' => $member->user->email,
                        ],
                    ];
                });
            }),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
