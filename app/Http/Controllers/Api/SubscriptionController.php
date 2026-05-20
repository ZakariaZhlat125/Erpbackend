<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Subscriptions", description: "User subscription endpoints")]
class SubscriptionController extends BaseApiController
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    #[OA\Get(
        path: '/subscriptions/my-subscription',
        summary: "Get user's active subscription",
        tags: ['Subscriptions'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function mySubscription(): JsonResponse
    {   
        $userId = auth()->id();

        $subscription = $this->subscriptionService->getUserActiveSubscription($userId);
        if (!$subscription) {
            return $this->notFoundResponse('No active subscription found');
        }

        return $this->successResponse(
            new SubscriptionResource($subscription)
        );
    }

    #[OA\Post(
        path: '/subscriptions/subscribe',
        summary: 'Subscribe to a plan',
        tags: ['Subscriptions'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/SubscribeRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Subscription created'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
    public function subscribe(StoreSubscriptionRequest $request): JsonResponse
    {
        $userId = auth()->id();
        $subscription = $this->subscriptionService->subscribe(
            $userId,
            $request->plan_id,
            $request->boolean('is_trial', false)
        );

        return $this->createdResponse(
            new SubscriptionResource($subscription),
            'Subscription created successfully'
        );
    }

    #[OA\Post(
        path: '/subscriptions/{id}/unsubscribe',
        summary: 'Cancel subscription',
        tags: ['Subscriptions'],
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
    public function unsubscribe(UpdateSubscriptionRequest $request, int $id): JsonResponse
    {
        $subscription = $this->subscriptionService->findById($id);

        if (!$subscription || $subscription->user_id !== auth()->id()) {
            return $this->notFoundResponse();
        }

        $this->subscriptionService->unsubscribe($id, $request->reason);

        return $this->successResponse(
            null,
            'Subscription cancelled successfully'
        );
    }

    #[OA\Post(
        path: '/subscriptions/{id}/renew',
        summary: 'Renew subscription',
        tags: ['Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Subscription renewed'),
            new OA\Response(response: 404, description: 'Not found')
        ]
    )]
    public function renew(int $id): JsonResponse
    {
        $subscription = $this->subscriptionService->findById($id);

        if (!$subscription || $subscription->user_id !== auth()->id()) {
            return $this->notFoundResponse();
        }

        $renewedSubscription = $this->subscriptionService->renew($id);

        return $this->successResponse(
            new SubscriptionResource($renewedSubscription),
            'Subscription renewed successfully'
        );
    }

    #[OA\Get(
        path: '/subscriptions/my-subscription-history',
        summary: 'Get subscription history',
        tags: ['Subscriptions'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 15))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    public function myHistory(): JsonResponse
    {
        $userId = auth()->id();
        $subscriptions = $this->subscriptionService->search(
            ['user_id' => $userId],
            request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($subscriptions);
    }
}
