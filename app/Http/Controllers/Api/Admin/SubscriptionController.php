<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Admin\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin/Subscriptions", description: "Admin subscription management endpoints")]
class SubscriptionController extends BaseApiController
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    #[OA\Get(
        path: '/api/admin/subscriptions',
        summary: 'Get all subscriptions',
        tags: ['Admin/Subscriptions'],
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
        $data = $this->subscriptionService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    #[OA\Post(
        path: '/api/admin/subscriptions',
        summary: 'Create a new subscription',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/StoreSubscriptionRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Subscription created'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->validated());

        return $this->createdResponse(
            new SubscriptionResource($subscription)
        );
    }

    #[OA\Get(
        path: '/api/admin/subscriptions/{id}',
        summary: 'Get subscription by ID',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $subscription = $this->subscriptionService->findById($id);

        if (!$subscription) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new SubscriptionResource($subscription)
        );
    }

    #[OA\Put(
        path: '/api/admin/subscriptions/{id}',
        summary: 'Update subscription',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateSubscriptionRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Subscription updated'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function update(UpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        if (!$this->subscriptionService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->subscriptionService->update($id, $request->validated());
        $subscription = $this->subscriptionService->findById($id);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Resource updated successfully'
        );
    }

    #[OA\Delete(
        path: '/api/admin/subscriptions/{id}',
        summary: 'Delete subscription',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Subscription deleted'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        if (!$this->subscriptionService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->subscriptionService->delete($id);

        return $this->noContentResponse();
    }

    #[OA\Patch(
        path: '/api/admin/subscriptions/subscriptions/{id}/change-status',
        summary: 'Change subscription status',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ChangeStatusRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status changed'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function changeStatus(int $id): JsonResponse
    {
        if (!$this->subscriptionService->exists($id)) {
            return $this->notFoundResponse();
        }

        $status = request()->input('status');
        $result = $this->subscriptionService->changeStatus($id, $status);

        if (!$result) {
            return $this->errorResponse('Invalid status provided', 422);
        }

        $subscription = $this->subscriptionService->findById($id);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription status updated successfully'
        );
    }

    #[OA\Post(
        path: '/api/admin/subscriptions/subscriptions/subscribe-user',
        summary: 'Subscribe a user to a plan',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SubscribeUserRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'User subscribed'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function subscribeUser(): JsonResponse
    {
        request()->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'is_trial' => 'boolean',
        ]);

        $subscription = $this->subscriptionService->subscribe(
            request()->user_id,
            request()->plan_id,
            request()->boolean('is_trial', false)
        );

        return $this->createdResponse(
            new SubscriptionResource($subscription),
            'User subscribed successfully'
        );
    }

    #[OA\Post(
        path: '/api/admin/subscriptions/subscriptions/{id}/renew',
        summary: 'Renew subscription',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Subscription renewed'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function renewSubscription(int $id): JsonResponse
    {
        if (!$this->subscriptionService->exists($id)) {
            return $this->notFoundResponse();
        }

        $subscription = $this->subscriptionService->renew($id);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription renewed successfully'
        );
    }

    #[OA\Post(
        path: '/api/admin/subscriptions/subscriptions/{id}/cancel',
        summary: 'Cancel subscription',
        tags: ['Admin/Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/UnsubscribeRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Subscription cancelled'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function cancelSubscription(int $id): JsonResponse
    {
        if (!$this->subscriptionService->exists($id)) {
            return $this->notFoundResponse();
        }

        request()->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $this->subscriptionService->unsubscribe($id, request()->reason);
        $subscription = $this->subscriptionService->findById($id);

        return $this->successResponse(
            new SubscriptionResource($subscription),
            'Subscription cancelled successfully'
        );
    }
}
