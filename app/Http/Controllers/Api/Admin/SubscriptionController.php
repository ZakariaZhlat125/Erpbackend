<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Admin\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends BaseApiController
{
    public function __construct(
        protected SubscriptionService $subscriptionService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->subscriptionService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->validated());

        return $this->createdResponse(
            new SubscriptionResource($subscription)
        );
    }

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

    public function destroy(int $id): JsonResponse
    {
        if (!$this->subscriptionService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->subscriptionService->delete($id);

        return $this->noContentResponse();
    }

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
