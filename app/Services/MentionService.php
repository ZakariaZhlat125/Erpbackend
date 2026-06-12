<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Notifications\MentionedInComment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class MentionService
{
    protected string $mentionPattern = '/@\[([^\]]+)\]\((\d+)\)/';
    
    protected string $simpleMentionPattern = '/@(\w+)/u';

    public function parseMentions(string $text): array
    {
        $mentions = [];

        // Format: @[User Name](123)
        if (preg_match_all($this->mentionPattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $mentions[] = [
                    'raw' => $match[0],
                    'name' => $match[1],
                    'user_id' => (int) $match[2],
                ];
            }
        }

        return $mentions;
    }

    public function extractMentionedUserIds(string $text): array
    {
        $mentions = $this->parseMentions($text);
        return array_column($mentions, 'user_id');
    }

    public function getMentionedUsers(string $text): Collection
    {
        $userIds = $this->extractMentionedUserIds($text);
        
        if (empty($userIds)) {
            return collect();
        }

        return User::whereIn('id', $userIds)
            ->where('is_active', true)
            ->get();
    }

    public function processMentions(Comment $comment): void
    {
        $mentionedUserIds = $this->extractMentionedUserIds($comment->body);
        
        // Store mentioned user IDs
        $comment->update(['mentions_json' => $mentionedUserIds]);

        // Send notifications
        if (!empty($mentionedUserIds)) {
            $this->notifyMentionedUsers($comment, $mentionedUserIds);
        }
    }

    public function notifyMentionedUsers(Comment $comment, array $userIds): void
    {
        $users = User::whereIn('id', $userIds)
            ->where('is_active', true)
            ->where('id', '!=', $comment->user_id) // Don't notify self
            ->get();

        foreach ($users as $user) {
            $user->notify(new MentionedInComment($comment));
        }
    }

    public function renderMentions(string $text): string
    {
        return preg_replace_callback(
            $this->mentionPattern,
            function ($matches) {
                $name = $matches[1];
                $userId = $matches[2];
                return "<span class=\"mention\" data-user-id=\"{$userId}\">@{$name}</span>";
            },
            $text
        );
    }

    public function formatMention(User $user): string
    {
        return "@[{$user->name}]({$user->id})";
    }

    public function searchUsers(string $query, int $organizationId, int $limit = 10): Collection
    {
        return User::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->select(['id', 'name', 'email'])
            ->limit($limit)
            ->get();
    }

    public function getRecentlyMentioned(int $userId, int $limit = 5): Collection
    {
        return User::whereIn('id', function ($query) use ($userId) {
            $query->select('mentions_json')
                ->from('comments')
                ->where('user_id', $userId)
                ->whereNotNull('mentions_json')
                ->orderByDesc('created_at')
                ->limit(20);
        })
        ->select(['id', 'name', 'email'])
        ->limit($limit)
        ->get();
    }
}
