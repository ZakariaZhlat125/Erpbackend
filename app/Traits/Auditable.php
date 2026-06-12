<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\ActivityLogService;

trait Auditable
{
    protected static array $auditableExclude = [];
    
    protected array $oldAttributesForAudit = [];

    public static function bootAuditable(): void
    {
        static::creating(function ($model) {
            // Store nothing for creating - we log after creation
        });

        static::created(function ($model) {
            static::logAuditEvent($model, ActivityLog::ACTION_CREATED);
        });

        static::updating(function ($model) {
            // Store original values before update
            $model->oldAttributesForAudit = $model->getOriginal();
        });

        static::updated(function ($model) {
            // Only log if there are actual changes
            if ($model->wasChanged()) {
                static::logAuditEvent(
                    $model,
                    ActivityLog::ACTION_UPDATED,
                    $model->oldAttributesForAudit
                );
            }
        });

        static::deleting(function ($model) {
            static::logAuditEvent($model, ActivityLog::ACTION_DELETED);
        });

        // Support for soft deletes
        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                static::logAuditEvent($model, ActivityLog::ACTION_RESTORED);
            });
        }
    }

    protected static function logAuditEvent($model, string $action, ?array $oldValues = null): void
    {
        try {
            $service = app(ActivityLogService::class);
            
            $filteredOldValues = $oldValues ? static::filterAuditableAttributes($oldValues) : null;
            $filteredNewValues = $action !== ActivityLog::ACTION_DELETED 
                ? static::filterAuditableAttributes($model->getAttributes()) 
                : null;

            switch ($action) {
                case ActivityLog::ACTION_CREATED:
                    $service->logCreated($model);
                    break;
                case ActivityLog::ACTION_UPDATED:
                    $service->logUpdated($model, $filteredOldValues ?? []);
                    break;
                case ActivityLog::ACTION_DELETED:
                    $service->logDeleted($model);
                    break;
                case ActivityLog::ACTION_RESTORED:
                    $service->logCustomAction($action, $model);
                    break;
            }
        } catch (\Exception $e) {
            // Log error but don't break the main operation
            \Log::error('Audit logging failed: ' . $e->getMessage(), [
                'model' => get_class($model),
                'action' => $action,
            ]);
        }
    }

    protected static function filterAuditableAttributes(array $attributes): array
    {
        $exclude = array_merge(
            static::$auditableExclude,
            static::getDefaultExcludedAttributes()
        );

        return array_diff_key($attributes, array_flip($exclude));
    }

    protected static function getDefaultExcludedAttributes(): array
    {
        return [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
        ];
    }

    public function getAuditableExclude(): array
    {
        return static::$auditableExclude;
    }

    public function setAuditableExclude(array $exclude): void
    {
        static::$auditableExclude = $exclude;
    }

    public function logCustomAuditAction(string $action, ?array $oldValues = null, ?array $newValues = null, ?array $meta = null): void
    {
        try {
            $service = app(ActivityLogService::class);
            $service->logCustomAction($action, $this, $oldValues, $newValues, $meta);
        } catch (\Exception $e) {
            \Log::error('Custom audit logging failed: ' . $e->getMessage());
        }
    }

    public function getActivityLogs(int $perPage = 15)
    {
        return ActivityLog::query()
            ->bySubject(static::class, $this->getKey())
            ->with(['actor:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }
}
