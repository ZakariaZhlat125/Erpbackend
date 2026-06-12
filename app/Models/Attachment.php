<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'entity_id',
        'user_id',
        'filename',
        'original_name',
        'mime_type',
        'size',
        'disk',
        'path',
        'category',
        'description',
        'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'meta_json' => 'array',
        ];
    }

    // ==================== Categories ====================
    public const CATEGORY_DOCUMENT = 'document';
    public const CATEGORY_IMAGE = 'image';
    public const CATEGORY_CONTRACT = 'contract';
    public const CATEGORY_RECEIPT = 'receipt';
    public const CATEGORY_OTHER = 'other';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function entity(): MorphTo
    {
        return $this->morphTo('entity', 'entity_type', 'entity_id');
    }

    // ==================== Scopes ====================
    public function scopeForEntity($query, string $entityType, int $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeImages($query)
    {
        return $query->where('mime_type', 'like', 'image/%');
    }

    public function scopeDocuments($query)
    {
        return $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // ==================== Helpers ====================
    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getTemporaryUrl(int $minutes = 60): string
    {
        return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes($minutes));
    }

    public function getFullPath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }

    public function getSizeForHumans(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function delete(): bool
    {
        // Delete file from storage
        Storage::disk($this->disk)->delete($this->path);
        
        return parent::delete();
    }

    public function getExtension(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }

    public function getIcon(): string
    {
        return match (true) {
            $this->isImage() => 'image',
            $this->isPdf() => 'file-pdf',
            str_contains($this->mime_type ?? '', 'word') => 'file-word',
            str_contains($this->mime_type ?? '', 'excel') || str_contains($this->mime_type ?? '', 'spreadsheet') => 'file-excel',
            default => 'file',
        };
    }
}
