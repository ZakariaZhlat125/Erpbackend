<?php

namespace App\Services;

use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class OrganizationService extends BaseService
{
    public function __construct(
        protected OrganizationRepositoryInterface $organizationRepository
    ) {
        parent::__construct($organizationRepository);
    }

    public function bulkCreate(array $organizationsData): Collection
    {
        return $this->organizationRepository->bulkCreate($organizationsData);
    }

    public function findByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->organizationRepository->findByStatus($status, $perPage);
    }

    public function findByUserId(int $userId, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->organizationRepository->findByUserId($userId, $columns, $relations);
    }

    public function findWithCurrency(int $id): ?Model
    {
        return $this->organizationRepository->findWithCurrency($id);
    }

    public function forceDelete(int $id): bool
    {
        return $this->organizationRepository->forceDeleteById($id);
    }

    public function toggleStatus(int $id): ?Model
    {
        return $this->organizationRepository->toggleStatus($id);
    }
}
