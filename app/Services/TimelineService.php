<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Payment;
use App\Models\WorkflowApproval;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class TimelineService
{
    public function getTimeline(Model $entity, array $options = []): Collection
    {
        $entityType = get_class($entity);
        $entityId = $entity->getKey();

        $includeActivities = $options['include_activities'] ?? true;
        $includeComments = $options['include_comments'] ?? true;
        $includeAttachments = $options['include_attachments'] ?? true;
        $includeWorkflow = $options['include_workflow'] ?? true;
        $includeRelated = $options['include_related'] ?? true;
        $limit = $options['limit'] ?? 50;
        $from = $options['from'] ?? null;
        $to = $options['to'] ?? null;

        $timeline = collect();

        // 1. Activity Logs
        if ($includeActivities) {
            $activities = $this->getActivityLogs($entityType, $entityId, $from, $to);
            $timeline = $timeline->merge($activities);
        }

        // 2. Comments
        if ($includeComments) {
            $comments = $this->getComments($entityType, $entityId, $from, $to);
            $timeline = $timeline->merge($comments);
        }

        // 3. Attachments
        if ($includeAttachments) {
            $attachments = $this->getAttachments($entityType, $entityId, $from, $to);
            $timeline = $timeline->merge($attachments);
        }

        // 4. Workflow Approvals
        if ($includeWorkflow) {
            $approvals = $this->getWorkflowApprovals($entityType, $entityId, $from, $to);
            $timeline = $timeline->merge($approvals);
        }

        // 5. Related Items (e.g., Payments for Invoice)
        if ($includeRelated) {
            $related = $this->getRelatedItems($entity, $from, $to);
            $timeline = $timeline->merge($related);
        }

        // Sort by timestamp descending and limit
        return $timeline
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();
    }

    protected function getActivityLogs(string $entityType, int $entityId, ?string $from, ?string $to): Collection
    {
        $query = ActivityLog::query()
            ->bySubject($entityType, $entityId)
            ->with('actor:id,name,email')
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->get()->map(function ($log) {
            return [
                'id' => 'activity_' . $log->id,
                'type' => 'activity',
                'action' => $log->action,
                'icon' => $this->getActionIcon($log->action),
                'color' => $this->getActionColor($log->action),
                'title' => $this->getActivityTitle($log),
                'description' => $this->getActivityDescription($log),
                'user' => $log->actor ? [
                    'id' => $log->actor->id,
                    'name' => $log->actor->name,
                    'email' => $log->actor->email,
                ] : null,
                'changes' => $log->getChangedFields(),
                'meta' => $log->meta_json,
                'timestamp' => $log->created_at,
                'ip_address' => $log->ip_address,
            ];
        });
    }

    protected function getComments(string $entityType, int $entityId, ?string $from, ?string $to): Collection
    {
        $query = Comment::query()
            ->forEntity($entityType, $entityId)
            ->rootComments()
            ->with(['user:id,name,email', 'replies.user:id,name,email'])
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->get()->map(function ($comment) {
            return [
                'id' => 'comment_' . $comment->id,
                'type' => 'comment',
                'action' => 'commented',
                'icon' => 'message-circle',
                'color' => 'blue',
                'title' => 'Comment added',
                'description' => $comment->body,
                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'email' => $comment->user->email,
                ],
                'is_internal' => $comment->is_internal,
                'is_pinned' => $comment->is_pinned,
                'replies_count' => $comment->replies->count(),
                'replies' => $comment->replies->map(fn($r) => [
                    'id' => $r->id,
                    'body' => $r->body,
                    'user' => [
                        'id' => $r->user->id,
                        'name' => $r->user->name,
                    ],
                    'created_at' => $r->created_at->toISOString(),
                ]),
                'timestamp' => $comment->created_at,
            ];
        });
    }

    protected function getAttachments(string $entityType, int $entityId, ?string $from, ?string $to): Collection
    {
        $query = Attachment::query()
            ->forEntity($entityType, $entityId)
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }

        return $query->get()->map(function ($attachment) {
            return [
                'id' => 'attachment_' . $attachment->id,
                'type' => 'attachment',
                'action' => 'file_attached',
                'icon' => $attachment->getIcon(),
                'color' => 'purple',
                'title' => 'File attached',
                'description' => $attachment->original_name,
                'user' => [
                    'id' => $attachment->user->id,
                    'name' => $attachment->user->name,
                    'email' => $attachment->user->email,
                ],
                'file' => [
                    'id' => $attachment->id,
                    'name' => $attachment->original_name,
                    'size' => $attachment->getSizeForHumans(),
                    'mime_type' => $attachment->mime_type,
                    'category' => $attachment->category,
                    'url' => $attachment->getUrl(),
                ],
                'timestamp' => $attachment->created_at,
            ];
        });
    }

    protected function getWorkflowApprovals(string $entityType, int $entityId, ?string $from, ?string $to): Collection
    {
        $query = WorkflowApproval::query()
            ->whereHas('instance', function ($q) use ($entityType, $entityId) {
                $q->where('entity_type', $entityType)->where('entity_id', $entityId);
            })
            ->whereIn('status', ['approved', 'rejected', 'delegated'])
            ->with(['step', 'actedBy:id,name,email'])
            ->orderByDesc('acted_at');

        if ($from) {
            $query->where('acted_at', '>=', $from);
        }
        if ($to) {
            $query->where('acted_at', '<=', $to);
        }

        return $query->get()->map(function ($approval) {
            $actionText = match ($approval->status) {
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'delegated' => 'Delegated',
                default => $approval->status,
            };

            return [
                'id' => 'approval_' . $approval->id,
                'type' => 'workflow',
                'action' => 'workflow_' . $approval->status,
                'icon' => $this->getApprovalIcon($approval->status),
                'color' => $this->getApprovalColor($approval->status),
                'title' => "{$actionText} - {$approval->step?->name}",
                'description' => $approval->comments,
                'user' => $approval->actedBy ? [
                    'id' => $approval->actedBy->id,
                    'name' => $approval->actedBy->name,
                    'email' => $approval->actedBy->email,
                ] : null,
                'step_name' => $approval->step?->name,
                'timestamp' => $approval->acted_at,
            ];
        });
    }

    protected function getRelatedItems(Model $entity, ?string $from, ?string $to): Collection
    {
        $related = collect();

        // For Invoice: Get payments
        if ($entity instanceof \App\Models\Invoice) {
            $query = Payment::where('invoice_id', $entity->id)
                ->with('createdBy:id,name,email')
                ->orderByDesc('created_at');

            if ($from) {
                $query->where('created_at', '>=', $from);
            }
            if ($to) {
                $query->where('created_at', '<=', $to);
            }

            $payments = $query->get()->map(function ($payment) {
                return [
                    'id' => 'payment_' . $payment->id,
                    'type' => 'payment',
                    'action' => 'payment_recorded',
                    'icon' => 'credit-card',
                    'color' => 'green',
                    'title' => 'Payment recorded',
                    'description' => "Amount: {$payment->amount} {$payment->currency_code}",
                    'user' => $payment->createdBy ? [
                        'id' => $payment->createdBy->id,
                        'name' => $payment->createdBy->name,
                    ] : null,
                    'payment' => [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'method' => $payment->method,
                        'reference' => $payment->reference,
                    ],
                    'timestamp' => $payment->created_at,
                ];
            });

            $related = $related->merge($payments);
        }

        return $related;
    }

    // ==================== Helpers ====================
    protected function getActionIcon(string $action): string
    {
        return match ($action) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash',
            'restored' => 'refresh-cw',
            'approved' => 'check-circle',
            'cancelled' => 'x-circle',
            'login' => 'log-in',
            'logout' => 'log-out',
            'exported' => 'download',
            'imported' => 'upload',
            'status_changed' => 'activity',
            'price_changed' => 'dollar-sign',
            default => 'circle',
        };
    }

    protected function getActionColor(string $action): string
    {
        return match ($action) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'restored' => 'orange',
            'approved' => 'green',
            'cancelled' => 'red',
            'login', 'logout' => 'gray',
            'exported', 'imported' => 'purple',
            default => 'gray',
        };
    }

    protected function getApprovalIcon(string $status): string
    {
        return match ($status) {
            'approved' => 'check-circle',
            'rejected' => 'x-circle',
            'delegated' => 'user-plus',
            default => 'clock',
        };
    }

    protected function getApprovalColor(string $status): string
    {
        return match ($status) {
            'approved' => 'green',
            'rejected' => 'red',
            'delegated' => 'yellow',
            default => 'gray',
        };
    }

    protected function getActivityTitle(ActivityLog $log): string
    {
        $entityName = class_basename($log->subject_type);
        
        return match ($log->action) {
            'created' => "{$entityName} created",
            'updated' => "{$entityName} updated",
            'deleted' => "{$entityName} deleted",
            'restored' => "{$entityName} restored",
            'approved' => "{$entityName} approved",
            'cancelled' => "{$entityName} cancelled",
            'status_changed' => "Status changed",
            'price_changed' => "Price changed",
            default => ucfirst(str_replace('_', ' ', $log->action)),
        };
    }

    protected function getActivityDescription(ActivityLog $log): ?string
    {
        $changes = $log->getChangedFields();
        
        if (empty($changes)) {
            return null;
        }

        $descriptions = [];
        foreach ($changes as $field => $change) {
            $oldVal = $change['old'] ?? 'empty';
            $newVal = $change['new'] ?? 'empty';
            $descriptions[] = "{$field}: {$oldVal} → {$newVal}";
        }

        return implode(', ', array_slice($descriptions, 0, 3));
    }
}
