<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Project\StoreProjectRequest;
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
}
