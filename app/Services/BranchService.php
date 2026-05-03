<?php

namespace App\Services;

use App\Repositories\Contracts\BranchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BranchService extends BaseService
{
    public function __construct(
        protected BranchRepositoryInterface $branchRepository
    ) {
        parent::__construct($branchRepository);
    }

    public function allByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->branchRepository->allByOrganization($organizationId, $perPage);
    }

    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->branchRepository->findByOrganization($organizationId, $perPage);
    }

    public function findActiveByOrganization(int $organizationId): Collection
    {
        return $this->branchRepository->findActiveByOrganization($organizationId);
    }

    public function findByIdAndOrganization(int $id, int $organizationId): ?Model
    {
        return $this->branchRepository->findByIdAndOrganization($id, $organizationId);
    }

    public function createForOrganization(int $organizationId, array $data): Model
    {
        return $this->branchRepository->createForOrganization($organizationId, $data);
    }

    public function updateForOrganization(int $id, int $organizationId, array $data): ?Model
    {
        return $this->branchRepository->updateForOrganization($id, $organizationId, $data);
    }

    public function deleteForOrganization(int $id, int $organizationId): bool
    {
        return $this->branchRepository->deleteForOrganization($id, $organizationId);
    }

    public function toggleStatus(int $id): ?Model
    {
        return $this->branchRepository->toggleStatus($id);
    }
}
