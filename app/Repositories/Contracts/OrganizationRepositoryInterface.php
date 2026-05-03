<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface OrganizationRepositoryInterface extends RepositoryInterface
{
    public function findByStatus(string $status, int $perPage = 15): LengthAwarePaginator;

    public function findByUserId(int $userId, array $columns = ['*'], array $relations = []): Collection;

    public function findWithCurrency(int $id): ?Model;

    public function bulkCreate(array $organizationsData): Collection;

    public function forceDeleteById(int $id): bool;

    public function toggleStatus(int $id): ?Model;
}
