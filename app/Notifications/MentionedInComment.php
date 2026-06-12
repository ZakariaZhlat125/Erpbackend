<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentionedInComment extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Comment $comment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $commenter = $this->comment->user;
        $entityType = class_basename($this->comment->entity_type);
        
        return (new MailMessage)
            ->subject("You were mentioned in a comment")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$commenter->name} mentioned you in a comment on {$entityType} #{$this->comment->entity_id}")
            ->line("\"{$this->truncateBody()}\"")
            ->action('View Comment', $this->getCommentUrl())
            ->line('Thank you for using our application!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'mention',
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->comment->user_id,
            'commenter_name' => $this->comment->user->name,
            'entity_type' => $this->comment->entity_type,
            'entity_id' => $this->comment->entity_id,
            'body_preview' => $this->truncateBody(),
            'url' => $this->getCommentUrl(),
        ];
    }

    protected function truncateBody(int $length = 100): string
    {
        $body = strip_tags($this->comment->body);
        
        if (strlen($body) <= $length) {
            return $body;
        }

        return substr($body, 0, $length) . '...';
    }

    protected function getCommentUrl(): string
    {
        $entityType = strtolower(class_basename($this->comment->entity_type));
        
        return config('app.frontend_url', config('app.url')) 
            . "/{$entityType}s/{$this->comment->entity_id}#comment-{$this->comment->id}";
    }
}
