<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface BranchRepositoryInterface extends RepositoryInterface
{
    public function allByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator;

    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator;

    public function findActiveByOrganization(int $organizationId): Collection;

    public function findByIdAndOrganization(int $id, int $organizationId): ?Model;

    public function createForOrganization(int $organizationId, array $data): Model;

    public function updateForOrganization(int $id, int $organizationId, array $data): ?Model;

    public function deleteForOrganization(int $id, int $organizationId): bool;

    public function toggleStatus(int $id): ?Model;
}
