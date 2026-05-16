<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProgressProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;

class ProjectController extends BaseApiController
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->projectService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->create($request->validated());

        return $this->createdResponse(
            new ProjectResource($project)
        );
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->findById($id);

        if (!$project) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new ProjectResource($project)
        );
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        if (!$this->projectService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->projectService->update($id, $request->validated());
        $project = $this->projectService->findById($id);

        return $this->successResponse(
            new ProjectResource($project),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->projectService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->projectService->delete($id);

        return $this->noContentResponse();
    }

    public function addMember(int $id): JsonResponse
    {
        $project = $this->projectService->findById($id);

        if (!$project) {
            return $this->notFoundResponse();
        }

        // TODO: Validate member data
        // TODO: Implement AddProjectMemberAction
        // $memberData = request()->validate([...]);
        // $member = $project->members()->create($memberData);

        return $this->createdResponse(
            ['message' => 'Add member functionality not implemented yet']
        );
    }

    public function removeMember(int $projectId, int $userId): JsonResponse
    {
        $project = $this->projectService->findById($projectId);

        if (!$project) {
            return $this->notFoundResponse();
        }

        // TODO: Implement RemoveProjectMemberAction
        // $project->members()->where('user_id', $userId)->delete();

        return $this->noContentResponse();
    }

    public function dashboard(int $id): JsonResponse
    {
        try {
            $dashboard = $this->projectService->getDashboard($id);

            return $this->successResponse($dashboard, 'Project dashboard retrieved');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->projectService->getStatistics();

        return $this->successResponse($stats, 'Project statistics retrieved');
    }

    public function updateProgress(UpdateProgressProjectRequest $request, int $id): JsonResponse
    {
        if (!$this->projectService->exists($id)) {
            return $this->notFoundResponse();
        }

        try {
            $this->projectService->updateProgress($id, $request->validated()['progress_percent']);
            $project = $this->projectService->findById($id);

            return $this->successResponse(
                new ProjectResource($project),
                'Project progress updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function search(): JsonResponse
    {
        $criteria = request()->only([
            'code_like',
            'name_like',
            'description_like',
            'status',
            'party_id',
            'start_date_from',
            'start_date_to',
            'end_date_from',
            'end_date_to',
            'budget_amount_from',
            'budget_amount_to',
        ]);

        $perPage = request()->integer('per_page', 15);
        $results = $this->projectService->search($criteria, $perPage);

        return $this->paginatedResponse($results);
    }

    public function export(): mixed
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        // $projects = $this->projectService->getAll();
        // return Excel::download(new ProjectsExport($projects), 'projects.xlsx');

        return $this->errorResponse('Export functionality not implemented yet', 501);
    }
}
