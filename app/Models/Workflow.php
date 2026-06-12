<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'entity_type',
        'trigger_on',
        'trigger_field',
        'trigger_value',
        'is_active',
        'allow_parallel',
        'config_json',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'allow_parallel' => 'boolean',
            'config_json' => 'array',
        ];
    }

    // ==================== Trigger Types ====================
    public const TRIGGER_CREATED = 'created';
    public const TRIGGER_UPDATED = 'updated';
    public const TRIGGER_STATUS_CHANGED = 'status_changed';
    public const TRIGGER_MANUAL = 'manual';

    // ==================== Relationships ====================
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(WorkflowStep::class)->orderBy('order');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    // ==================== Scopes ====================
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeForOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    public function scopeTriggeredBy($query, string $trigger)
    {
        return $query->where('trigger_on', $trigger);
    }

    // ==================== Helpers ====================
    public function getFirstStep(): ?WorkflowStep
    {
        return $this->steps()->orderBy('order')->first();
    }

    public function getStepByOrder(int $order): ?WorkflowStep
    {
        return $this->steps()->where('order', $order)->first();
    }

    public function getNextStep(WorkflowStep $currentStep): ?WorkflowStep
    {
        return $this->steps()
            ->where('order', '>', $currentStep->order)
            ->orderBy('order')
            ->first();
    }

    public function getTotalSteps(): int
    {
        return $this->steps()->count();
    }

    public function canStartNewInstance(Model $entity): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if (!$this->allow_parallel) {
            $existingInstance = $this->instances()
                ->where('entity_type', get_class($entity))
                ->where('entity_id', $entity->getKey())
                ->whereIn('status', ['pending', 'in_progress'])
                ->exists();

            if ($existingInstance) {
                return false;
            }
        }

        return true;
    }
}
