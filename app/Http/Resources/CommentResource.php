<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\MentionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity_type' => class_basename($this->entity_type),
            'entity_id' => $this->entity_id,
            'parent_id' => $this->parent_id,
            'body' => $this->body,
            'body_html' => $this->getBodyWithMentions(),
            'is_internal' => $this->is_internal,
            'is_pinned' => $this->is_pinned,
            'mentioned_user_ids' => $this->mentions_json ?? [],
            'mentioned_users' => $this->getMentionedUsers(),
            'replies_count' => $this->whenLoaded('replies', fn() => $this->replies->count()),
            
            'user' => $this->when($this->relationLoaded('user') || $this->user, fn() => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'email' => $this->user?->email,
            ]),
            
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
            
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    protected function getBodyWithMentions(): string
    {
        $mentionService = app(MentionService::class);
        return $mentionService->renderMentions($this->body);
    }

    protected function getMentionedUsers(): array
    {
        $userIds = $this->mentions_json ?? [];
        
        if (empty($userIds)) {
            return [];
        }

        return User::whereIn('id', $userIds)
            ->select(['id', 'name', 'email'])
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ])
            ->toArray();
    }
}
