<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => class_basename($this->entity_type),
            'entity_id' => $this->entity_id,
            'filename' => $this->filename,
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'size_human' => $this->getSizeForHumans(),
            'category' => $this->category,
            'description' => $this->description,
            'icon' => $this->getIcon(),
            'is_image' => $this->isImage(),
            'is_pdf' => $this->isPdf(),
            'extension' => $this->getExtension(),
            'url' => $this->getUrl(),
            
            'user' => $this->when($this->relationLoaded('user') || $this->user, fn() => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
