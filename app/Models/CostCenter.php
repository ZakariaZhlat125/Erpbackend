<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CostCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'parent_id',
        'code',
        'name',
        'description',
        'type',
        'is_active',
        'level',
        'path',
        'budget',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'level' => 'integer',
            'budget' => 'decimal:2',
        ];
    }

    // ==================== Types ====================
    public const TYPE_BRANCH = 'branch';
    public const TYPE_DEPARTMENT = 'department';
    public const TYPE_PROJECT = 'project';
    public const TYPE_OTHER = 'other';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CostCenter::class, 'parent_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // ==================== Helpers ====================
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function getAncestors(): \Illuminate\Database\Eloquent\Collection
    {
        $ancestors = collect();
        $current = $this->parent;

        while ($current) {
            $ancestors->prepend($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    public function getDescendants(): \Illuminate\Database\Eloquent\Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }

        return $descendants;
    }

    public function updatePath(): void
    {
        $path = $this->code;
        $level = 0;

        if ($this->parent) {
            $path = $this->parent->path . '/' . $this->code;
            $level = $this->parent->level + 1;
        }

        $this->update(['path' => $path, 'level' => $level]);

        // Update children
        foreach ($this->children as $child) {
            $child->updatePath();
        }
    }

    public function getTotalBudget(): float
    {
        $total = $this->budget ?? 0;

        foreach ($this->children as $child) {
            $total += $child->getTotalBudget();
        }

        return $total;
    }

    public function getActualSpending(?string $from = null, ?string $to = null): float
    {
        $query = $this->journalLines()
            ->whereHas('batch', function ($q) {
                $q->where('status', 'posted');
            });

        if ($from) {
            $query->whereHas('batch', fn($q) => $q->where('date', '>=', $from));
        }
        if ($to) {
            $query->whereHas('batch', fn($q) => $q->where('date', '<=', $to));
        }

        return $query->sum('debit') - $query->sum('credit');
    }
}
