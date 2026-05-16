<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class UserController extends BaseApiController
{
    public function __construct(
        protected UserService $userService
    ) {}

    #[OA\Get(
        path: '/api/admin/users',
        summary: 'Get all users',
        tags: ['Admin/Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function index(): JsonResponse
    {
        $data = $this->userService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    #[OA\Post(
        path: '/api/admin/users',
        summary: 'Create a new user',
        tags: ['Admin/Users'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreUserRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'User created'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return $this->createdResponse(
            new UserResource($user)
        );
    }

    #[OA\Get(
        path: '/api/admin/users/{id}',
        summary: 'Get user by ID',
        tags: ['Admin/Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findById($id);

        if (!$user) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new UserResource($user)
        );
    }

    #[OA\Put(
        path: '/api/admin/users/{id}',
        summary: 'Update user',
        tags: ['Admin/Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateUserRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'User updated'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        if (!$this->userService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->userService->update($id, $request->validated());
        $user = $this->userService->findById($id);

        return $this->successResponse(
            new UserResource($user),
            'Resource updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/admin/users/{id}',
        summary: 'Delete user',
        tags: ['Admin/Users'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'User deleted'),
            new OA\Response(response: 404, description: 'User not found')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        if (!$this->userService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->userService->delete($id);

        return $this->noContentResponse();
    }
}
