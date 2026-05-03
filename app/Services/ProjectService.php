<?php

namespace App\Services;

use App\Models\Project;
use App\Repositories\Contracts\ProjectRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProjectService extends BaseService
{
    public function __construct(ProjectRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getDashboard(int $id): array
    {
        $project = $this->findById($id, ['*'], ['tasks', 'party']);
        
        if (!$project) {
            throw new \Exception('Project not found');
        }

        $tasks = $project->tasks;
        $taskStats = [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'on_hold' => $tasks->where('status', 'on_hold')->count(),
        ];

        return [
            'project' => $project,
            'task_stats' => $taskStats,
            'completion_rate' => $taskStats['total'] > 0 
                ? round(($taskStats['completed'] / $taskStats['total']) * 100, 2) 
                : 0,
        ];
    }

    public function getStatistics(): array
    {
        $query = Project::query();

        $stats = [
            'total_count' => (clone $query)->count(),
            'planning_count' => (clone $query)->where('status', 'planning')->count(),
            'active_count' => (clone $query)->where('status', 'active')->count(),
            'on_hold_count' => (clone $query)->where('status', 'on_hold')->count(),
            'completed_count' => (clone $query)->where('status', 'completed')->count(),
            'cancelled_count' => (clone $query)->where('status', 'cancelled')->count(),
            'total_budget' => (clone $query)->sum('budget_amount'),
            'avg_progress' => (clone $query)->where('status', 'active')->avg('progress_percent'),
        ];

        $overdue = (clone $query)
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->whereNotNull('end_date')
            ->where('end_date', '<', now())
            ->count();

        $stats['overdue_count'] = $overdue;

        return $stats;
    }

    public function updateProgress(int $id, int $progressPercent): bool
    {
        if ($progressPercent < 0 || $progressPercent > 100) {
            throw new \Exception('Progress must be between 0 and 100');
        }

        return $this->update($id, ['progress_percent' => $progressPercent]);
    }
}
