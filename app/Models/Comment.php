<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'entity_type',
        'entity_id',
        'user_id',
        'parent_id',
        'body',
        'is_internal',
        'is_pinned',
        'mentions_json',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'is_pinned' => 'boolean',
            'mentions_json' => 'array',
        ];
    }

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->orderBy('created_at');
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

    public function scopeRootComments($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternal($query)
    {
        return $query->where('is_internal', true);
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    // ==================== Helpers ====================
    public function isReply(): bool
    {
        return $this->parent_id !== null;
    }

    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }

    public function getMentionedUserIds(): array
    {
        return $this->mentions_json ?? [];
    }

    public function togglePin(): bool
    {
        $this->is_pinned = !$this->is_pinned;
        return $this->save();
    }
}
