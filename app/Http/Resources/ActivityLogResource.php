<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'actor_id' => $this->actor_id,
            'action' => $this->action,
            'subject_type' => $this->getShortSubjectType(),
            'subject_type_full' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'old_values' => $this->old_values_json,
            'new_values' => $this->new_values_json,
            'changed_fields' => $this->getChangedFields(),
            'meta' => $this->meta_json,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'device_info' => $this->getDeviceInfo(),
            'is_sensitive' => $this->isSensitive(),
            'created_at' => $this->created_at?->toISOString(),

            // Relationships
            'actor' => $this->when($this->relationLoaded('actor') || $this->actor, function () {
                return [
                    'id' => $this->actor?->id,
                    'name' => $this->actor?->name,
                    'email' => $this->actor?->email,
                ];
            }),
            'subject' => $this->when($this->relationLoaded('subject'), function () {
                return $this->formatSubject();
            }),
        ];
    }

    protected function getShortSubjectType(): string
    {
        $type = $this->subject_type ?? '';
        return class_basename($type);
    }

    protected function formatSubject(): ?array
    {
        if (!$this->subject) {
            return null;
        }

        $subject = $this->subject;
        
        return [
            'id' => $subject->id,
            'name' => $subject->name ?? $subject->title ?? $subject->number ?? "#{$subject->id}",
            'type' => class_basename($subject),
        ];
    }
}
