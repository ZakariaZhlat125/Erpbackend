<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Repositories\Contracts\ActivityLogRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogRepository extends BaseRepository implements ActivityLogRepositoryInterface
{
    public function __construct(ActivityLog $model)
    {
        parent::__construct($model);
    }

    public function getForOrganization(int $organizationId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->forOrganization($organizationId)
            ->with(['actor:id,name,email'])
            ->orderByDesc('created_at');

        if (!empty($filters['actor_id'])) {
            $query->byActor($filters['actor_id']);
        }

        if (!empty($filters['action'])) {
            $query->byAction($filters['action']);
        }

        if (!empty($filters['subject_type'])) {
            $query->bySubject($filters['subject_type'], $filters['subject_id'] ?? null);
        }

        if (!empty($filters['from']) || !empty($filters['to'])) {
            $query->betweenDates($filters['from'] ?? null, $filters['to'] ?? null);
        }

        if (!empty($filters['sensitive_only'])) {
            $query->sensitive();
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                  ->orWhere('subject_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        return $query->paginate($perPage);
    }

    public function getBySubject(string $subjectType, int $subjectId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->bySubject($subjectType, $subjectId)
            ->with(['actor:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getByActor(int $actorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->byActor($actorId)
            ->with(['actor:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getSensitiveLogs(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->forOrganization($organizationId)
            ->sensitive()
            ->with(['actor:id,name,email'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getStatistics(int $organizationId, ?string $from = null, ?string $to = null): array
    {
        $query = $this->model->newQuery()
            ->forOrganization($organizationId)
            ->betweenDates($from, $to);

        $totalLogs = (clone $query)->count();
        $sensitiveLogs = (clone $query)->sensitive()->count();

        $byAction = (clone $query)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $bySubjectType = (clone $query)
            ->selectRaw('subject_type, COUNT(*) as count')
            ->groupBy('subject_type')
            ->pluck('count', 'subject_type')
            ->toArray();

        $topActors = (clone $query)
            ->selectRaw('actor_id, COUNT(*) as count')
            ->whereNotNull('actor_id')
            ->groupBy('actor_id')
            ->orderByDesc('count')
            ->limit(10)
            ->with('actor:id,name,email')
            ->get()
            ->map(fn($log) => [
                'actor_id' => $log->actor_id,
                'actor_name' => $log->actor?->name,
                'count' => $log->count,
            ]);

        $recentActivity = (clone $query)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderByDesc('date')
            ->limit(30)
            ->pluck('count', 'date')
            ->toArray();

        return [
            'total_logs' => $totalLogs,
            'sensitive_logs' => $sensitiveLogs,
            'by_action' => $byAction,
            'by_subject_type' => $bySubjectType,
            'top_actors' => $topActors,
            'recent_activity' => $recentActivity,
        ];
    }
}
