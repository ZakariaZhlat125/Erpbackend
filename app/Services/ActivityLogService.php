<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService extends BaseService
{
    protected static ?string $currentIp = null;
    protected static ?string $currentUserAgent = null;

    public function __construct(ActivityLogRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    // ==================== Context Methods ====================
    public static function setContext(?string $ip = null, ?string $userAgent = null): void
    {
        self::$currentIp = $ip;
        self::$currentUserAgent = $userAgent;
    }

    public static function getContext(): array
    {
        return [
            'ip' => self::$currentIp ?? Request::ip(),
            'user_agent' => self::$currentUserAgent ?? Request::userAgent(),
        ];
    }

    // ==================== Logging Methods ====================
    public function log(
        string $action,
        Model $subject,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null
    ): ActivityLog {
        $context = self::getContext();
        $user = Auth::user();

        return $this->repository->create([
            'organization_id' => $this->getOrganizationId($subject, $user),
            'actor_id' => $user?->id,
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->getKey(),
            'old_values_json' => $oldValues,
            'new_values_json' => $newValues,
            'meta_json' => $meta,
            'ip_address' => $context['ip'],
            'user_agent' => $context['user_agent'],
            'created_at' => now(),
        ]);
    }

    public function logCreated(Model $subject, ?array $meta = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_CREATED,
            $subject,
            null,
            $subject->toArray(),
            $meta
        );
    }

    public function logUpdated(Model $subject, array $oldValues, ?array $meta = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_UPDATED,
            $subject,
            $oldValues,
            $subject->toArray(),
            $meta
        );
    }

    public function logDeleted(Model $subject, ?array $meta = null): ActivityLog
    {
        return $this->log(
            ActivityLog::ACTION_DELETED,
            $subject,
            $subject->toArray(),
            null,
            $meta
        );
    }

    public function logCustomAction(
        string $action,
        Model $subject,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $meta = null
    ): ActivityLog {
        return $this->log($action, $subject, $oldValues, $newValues, $meta);
    }

    public function logAuth(string $action, ?Model $user = null, ?array $meta = null): ActivityLog
    {
        $context = self::getContext();
        $currentUser = $user ?? Auth::user();

        return $this->repository->create([
            'organization_id' => $currentUser?->organization_id ?? 0,
            'actor_id' => $currentUser?->id,
            'action' => $action,
            'subject_type' => $currentUser ? get_class($currentUser) : 'App\Models\User',
            'subject_id' => $currentUser?->id ?? 0,
            'old_values_json' => null,
            'new_values_json' => null,
            'meta_json' => array_merge($meta ?? [], [
                'auth_action' => true,
            ]),
            'ip_address' => $context['ip'],
            'user_agent' => $context['user_agent'],
            'created_at' => now(),
        ]);
    }

    // ==================== Query Methods ====================
    public function getForOrganization(int $organizationId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForOrganization($organizationId, $filters, $perPage);
    }

    public function getBySubject(string $subjectType, int $subjectId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getBySubject($subjectType, $subjectId, $perPage);
    }

    public function getByActor(int $actorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByActor($actorId, $perPage);
    }

    public function getSensitiveLogs(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getSensitiveLogs($organizationId, $perPage);
    }

    public function getStatistics(int $organizationId, ?string $from = null, ?string $to = null): array
    {
        return $this->repository->getStatistics($organizationId, $from, $to);
    }

    // ==================== Helper Methods ====================
    protected function getOrganizationId(Model $subject, ?object $user): int
    {
        if (method_exists($subject, 'getOrganizationId')) {
            return $subject->getOrganizationId();
        }

        if (isset($subject->organization_id)) {
            return $subject->organization_id;
        }

        if ($user && isset($user->organization_id)) {
            return $user->organization_id;
        }

        return 0;
    }

    public function getAvailableActions(): array
    {
        return [
            ActivityLog::ACTION_CREATED,
            ActivityLog::ACTION_UPDATED,
            ActivityLog::ACTION_DELETED,
            ActivityLog::ACTION_RESTORED,
            ActivityLog::ACTION_LOGIN,
            ActivityLog::ACTION_LOGOUT,
            ActivityLog::ACTION_LOGIN_FAILED,
            ActivityLog::ACTION_PASSWORD_RESET,
            ActivityLog::ACTION_APPROVED,
            ActivityLog::ACTION_CANCELLED,
            ActivityLog::ACTION_EXPORTED,
            ActivityLog::ACTION_IMPORTED,
            ActivityLog::ACTION_PERMISSION_CHANGED,
            ActivityLog::ACTION_PRICE_CHANGED,
            ActivityLog::ACTION_STATUS_CHANGED,
        ];
    }

    public function getAvailableSubjectTypes(): array
    {
        return [
            'App\Models\Invoice',
            'App\Models\Payment',
            'App\Models\Product',
            'App\Models\Party',
            'App\Models\Employee',
            'App\Models\User',
            'App\Models\Organization',
            'App\Models\Project',
            'App\Models\Task',
        ];
    }
}
