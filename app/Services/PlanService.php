<?php

namespace App\Services;

use App\Repositories\Contracts\PlanRepositoryInterface;

class PlanService extends BaseService
{
    public function __construct(PlanRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function changeStatus(int $id): bool
    {
        $plan = $this->repository->findById($id);
        
        if (!$plan) {
            return false;
        }

        return $this->repository->update($id, [
            'is_active' => !$plan->is_active
        ]);
    }
}
