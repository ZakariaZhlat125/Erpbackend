<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

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
