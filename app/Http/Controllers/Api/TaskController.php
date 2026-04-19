<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;

class TaskController extends BaseApiController
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->taskService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated());

        return $this->createdResponse(
            new TaskResource($task)
        );
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->findById($id);

        if (!$task) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new TaskResource($task)
        );
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        if (!$this->taskService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->taskService->update($id, $request->validated());
        $task = $this->taskService->findById($id);

        return $this->successResponse(
            new TaskResource($task),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->taskService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->taskService->delete($id);

        return $this->noContentResponse();
    }
}
