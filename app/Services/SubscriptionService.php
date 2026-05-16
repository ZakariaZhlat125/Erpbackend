<?php

namespace App\Services;

use App\Models\Plan;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Carbon\Carbon;

class SubscriptionService extends BaseService
{
    public function __construct(SubscriptionRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function subscribe(int $userId, int $planId, bool $isTrial = false): mixed
    {
        $plan = Plan::findOrFail($planId);
        
        $startDate = Carbon::now();
        $endDate = $this->calculateEndDate($startDate, $plan->billing_cycle);
        
        $data = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $isTrial ? 'trial' : 'active',
            'price_paid' => $isTrial ? 0 : $plan->price,
            'billing_cycle' => $plan->billing_cycle,
            'auto_renew' => true,
        ];

        if ($isTrial) {
            $data['trial_ends_at'] = Carbon::now()->addDays(14);
        }

        $this->cancelActiveSubscriptions($userId);

        return $this->repository->create($data);
    }

    public function unsubscribe(int $subscriptionId, ?string $reason = null): bool
    {
        $subscription = $this->repository->findById($subscriptionId);
        
        if (!$subscription) {
            return false;
        }

        return $this->repository->update($subscriptionId, [
            'status' => 'cancelled',
            'cancelled_at' => Carbon::now(),
            'cancellation_reason' => $reason,
            'auto_renew' => false,
        ]);
    }

    public function renew(int $subscriptionId): mixed
    {
        $subscription = $this->repository->findById($subscriptionId, ['*'], ['plan']);
        
        if (!$subscription) {
            return false;
        }

        $plan = $subscription->plan;
        $startDate = Carbon::now();
        $endDate = $this->calculateEndDate($startDate, $plan->billing_cycle);

        $this->repository->update($subscriptionId, [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'active',
            'price_paid' => $plan->price,
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);

        return $this->repository->findById($subscriptionId);
    }

    public function changeStatus(int $subscriptionId, string $status): bool
    {
        $validStatuses = ['active', 'expired', 'cancelled', 'trial'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }

        return $this->repository->update($subscriptionId, [
            'status' => $status,
        ]);
    }

    public function getUserActiveSubscription(int $userId): mixed
    {
        return $this->repository->findByField('user_id', $userId)
            ->where('status', 'active')
            ->where('end_date', '>=', Carbon::now())
            ->first();
    }

    protected function calculateEndDate(Carbon $startDate, string $billingCycle): Carbon
    {
        return match($billingCycle) {
            'monthly' => $startDate->copy()->addMonth(),
            'yearly' => $startDate->copy()->addYear(),
            'lifetime' => $startDate->copy()->addYears(100),
            default => $startDate->copy()->addMonth(),
        };
    }

    protected function cancelActiveSubscriptions(int $userId): void
    {
        $activeSubscriptions = $this->repository->findByField('user_id', $userId)
            ->whereIn('status', ['active', 'trial']);

        foreach ($activeSubscriptions as $subscription) {
            $this->repository->update($subscription->id, [
                'status' => 'cancelled',
                'cancelled_at' => Carbon::now(),
                'auto_renew' => false,
            ]);
        }
    }
}
