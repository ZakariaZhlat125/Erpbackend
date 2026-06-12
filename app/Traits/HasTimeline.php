<?php

namespace App\Traits;

use App\Models\Attachment;
use App\Models\Comment;
use App\Services\TimelineService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait HasTimeline
{
    // ==================== Relationships ====================
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'entity', 'entity_type', 'entity_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'entity', 'entity_type', 'entity_id');
    }

    // ==================== Timeline ====================
    public function getTimeline(array $options = []): Collection
    {
        $service = app(TimelineService::class);
        return $service->getTimeline($this, $options);
    }

    // ==================== Comments ====================
    public function addComment(string $body, array $options = []): Comment
    {
        return $this->comments()->create([
            'organization_id' => $this->organization_id ?? Auth::user()->organization_id,
            'user_id' => Auth::id(),
            'body' => $body,
            'parent_id' => $options['parent_id'] ?? null,
            'is_internal' => $options['is_internal'] ?? false,
            'mentions_json' => $options['mentions'] ?? null,
        ]);
    }

    public function getComments(bool $includeInternal = true): Collection
    {
        $query = $this->comments()
            ->rootComments()
            ->with(['user:id,name,email', 'replies.user:id,name,email'])
            ->orderByDesc('created_at');

        if (!$includeInternal) {
            $query->public();
        }

        return $query->get();
    }

    public function getPinnedComments(): Collection
    {
        return $this->comments()
            ->pinned()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getCommentsCount(): int
    {
        return $this->comments()->count();
    }

    // ==================== Attachments ====================
    public function addAttachment(UploadedFile $file, array $options = []): Attachment
    {
        $disk = $options['disk'] ?? 'local';
        $directory = $options['directory'] ?? 'attachments/' . class_basename($this) . '/' . $this->getKey();
        
        // Store file
        $path = $file->store($directory, $disk);
        
        return $this->attachments()->create([
            'organization_id' => $this->organization_id ?? Auth::user()->organization_id,
            'user_id' => Auth::id(),
            'filename' => basename($path),
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'disk' => $disk,
            'path' => $path,
            'category' => $options['category'] ?? null,
            'description' => $options['description'] ?? null,
        ]);
    }

    public function addAttachmentFromUrl(string $url, string $filename, array $options = []): Attachment
    {
        $disk = $options['disk'] ?? 'local';
        $directory = $options['directory'] ?? 'attachments/' . class_basename($this) . '/' . $this->getKey();
        
        // Download and store
        $contents = file_get_contents($url);
        $path = $directory . '/' . $filename;
        Storage::disk($disk)->put($path, $contents);
        
        return $this->attachments()->create([
            'organization_id' => $this->organization_id ?? Auth::user()->organization_id,
            'user_id' => Auth::id(),
            'filename' => $filename,
            'original_name' => $filename,
            'mime_type' => $options['mime_type'] ?? null,
            'size' => strlen($contents),
            'disk' => $disk,
            'path' => $path,
            'category' => $options['category'] ?? null,
            'description' => $options['description'] ?? null,
        ]);
    }

    public function getAttachments(?string $category = null): Collection
    {
        $query = $this->attachments()
            ->with('user:id,name,email')
            ->orderByDesc('created_at');

        if ($category) {
            $query->byCategory($category);
        }

        return $query->get();
    }

    public function getImages(): Collection
    {
        return $this->attachments()
            ->images()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getDocuments(): Collection
    {
        return $this->attachments()
            ->documents()
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getAttachmentsCount(): int
    {
        return $this->attachments()->count();
    }

    public function getTotalAttachmentsSize(): int
    {
        return $this->attachments()->sum('size');
    }

    // ==================== Cleanup ====================
    public function deleteAllAttachments(): int
    {
        $attachments = $this->attachments;
        $count = $attachments->count();

        foreach ($attachments as $attachment) {
            $attachment->delete();
        }

        return $count;
    }
}
