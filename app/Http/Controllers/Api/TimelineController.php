<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TimelineResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\AttachmentResource;
use App\Models\Comment;
use App\Models\Attachment;
use App\Services\TimelineService;
use App\Services\MentionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TimelineController extends BaseApiController
{
    protected array $allowedEntities = [
        'invoice' => \App\Models\Invoice::class,
        'payment' => \App\Models\Payment::class,
        'party' => \App\Models\Party::class,
        'product' => \App\Models\Product::class,
        'employee' => \App\Models\Employee::class,
        'project' => \App\Models\Project::class,
        'task' => \App\Models\Task::class,
    ];

    public function __construct(
        protected TimelineService $timelineService,
        protected MentionService $mentionService
    ) {}

    // ==================== Timeline ====================
    public function timeline(string $entityType, int $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        if (!$entity) {
            return $this->notFoundResponse();
        }

        $options = [
            'include_activities' => request()->boolean('activities', true),
            'include_comments' => request()->boolean('comments', true),
            'include_attachments' => request()->boolean('attachments', true),
            'include_workflow' => request()->boolean('workflow', true),
            'include_related' => request()->boolean('related', true),
            'limit' => request()->integer('limit', 50),
            'from' => request()->input('from'),
            'to' => request()->input('to'),
        ];

        $timeline = $this->timelineService->getTimeline($entity, $options);

        return $this->successResponse(
            TimelineResource::collection($timeline)
        );
    }

    // ==================== Comments ====================
    public function comments(string $entityType, int $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        if (!$entity) {
            return $this->notFoundResponse();
        }

        $includeInternal = Auth::user()->can('view_internal_comments');

        $query = Comment::forEntity(get_class($entity), $entityId)
            ->rootComments()
            ->with(['user:id,name,email', 'replies.user:id,name,email'])
            ->orderByDesc('created_at');

        if (!$includeInternal) {
            $query->public();
        }

        $comments = $query->paginate(request()->integer('per_page', 15));

        return $this->paginatedResponse($comments, CommentResource::class);
    }

    public function storeComment(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        if (!$entity) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:comments,id',
            'is_internal' => 'boolean',
            'mentions' => 'nullable|array',
            'mentions.*' => 'integer|exists:users,id',
        ]);

        $comment = Comment::create([
            'organization_id' => Auth::user()->organization_id,
            'entity_type' => get_class($entity),
            'entity_id' => $entityId,
            'user_id' => Auth::id(),
            'body' => $validated['body'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        // Process mentions and send notifications
        $this->mentionService->processMentions($comment);

        return $this->createdResponse(
            new CommentResource($comment->load('user')),
            'Comment added successfully'
        );
    }

    public function updateComment(Request $request, int $commentId): JsonResponse
    {
        $comment = Comment::find($commentId);
        
        if (!$comment) {
            return $this->notFoundResponse();
        }

        if ($comment->user_id !== Auth::id()) {
            return $this->errorResponse('You can only edit your own comments', 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        $comment->update($validated);

        return $this->successResponse(
            new CommentResource($comment),
            'Comment updated successfully'
        );
    }

    public function deleteComment(int $commentId): JsonResponse
    {
        $comment = Comment::find($commentId);
        
        if (!$comment) {
            return $this->notFoundResponse();
        }

        if ($comment->user_id !== Auth::id() && !Auth::user()->can('delete_any_comment')) {
            return $this->errorResponse('You can only delete your own comments', 403);
        }

        $comment->delete();

        return $this->noContentResponse();
    }

    public function togglePinComment(int $commentId): JsonResponse
    {
        $comment = Comment::find($commentId);
        
        if (!$comment) {
            return $this->notFoundResponse();
        }

        $comment->togglePin();

        return $this->successResponse(
            new CommentResource($comment),
            $comment->is_pinned ? 'Comment pinned' : 'Comment unpinned'
        );
    }

    // ==================== Attachments ====================
    public function attachments(string $entityType, int $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        if (!$entity) {
            return $this->notFoundResponse();
        }

        $query = Attachment::forEntity(get_class($entity), $entityId)
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($category = request()->input('category')) {
            $query->byCategory($category);
        }

        $attachments = $query->paginate(request()->integer('per_page', 15));

        return $this->paginatedResponse($attachments, AttachmentResource::class);
    }

    public function storeAttachment(Request $request, string $entityType, int $entityId): JsonResponse
    {
        $entity = $this->resolveEntity($entityType, $entityId);
        
        if (!$entity) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'category' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $file = $request->file('file');
        $directory = 'attachments/' . $entityType . '/' . $entityId;
        $path = $file->store($directory, 'local');

        $attachment = Attachment::create([
            'organization_id' => Auth::user()->organization_id,
            'entity_type' => get_class($entity),
            'entity_id' => $entityId,
            'user_id' => Auth::id(),
            'filename' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => 'local',
            'path' => $path,
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return $this->createdResponse(
            new AttachmentResource($attachment->load('user')),
            'File uploaded successfully'
        );
    }

    public function deleteAttachment(int $attachmentId): JsonResponse
    {
        $attachment = Attachment::find($attachmentId);
        
        if (!$attachment) {
            return $this->notFoundResponse();
        }

        if ($attachment->user_id !== Auth::id() && !Auth::user()->can('delete_any_attachment')) {
            return $this->errorResponse('You can only delete your own attachments', 403);
        }

        $attachment->delete();

        return $this->noContentResponse();
    }

    public function downloadAttachment(int $attachmentId)
    {
        $attachment = Attachment::find($attachmentId);
        
        if (!$attachment) {
            return $this->notFoundResponse();
        }

        return response()->download(
            $attachment->getFullPath(),
            $attachment->original_name
        );
    }

    // ==================== Mentions ====================
    public function searchUsers(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        
        if (strlen($query) < 2) {
            return $this->successResponse([]);
        }

        $users = $this->mentionService->searchUsers(
            $query,
            Auth::user()->organization_id,
            $request->integer('limit', 10)
        );

        return $this->successResponse(
            $users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mention' => $this->mentionService->formatMention($user),
            ])
        );
    }

    public function recentMentions(): JsonResponse
    {
        $users = $this->mentionService->getRecentlyMentioned(Auth::id(), 5);

        return $this->successResponse(
            $users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mention' => $this->mentionService->formatMention($user),
            ])
        );
    }

    // ==================== Helpers ====================
    protected function resolveEntity(string $type, int $id): ?object
    {
        $modelClass = $this->allowedEntities[$type] ?? null;
        
        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($id);
    }
}
