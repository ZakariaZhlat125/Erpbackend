<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Repositories\Contracts\BranchRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }

    public function allByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('organization_id', $organizationId)->paginate($perPage);
    }

    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('organization_id', $organizationId)->paginate($perPage);
    }

    public function findActiveByOrganization(int $organizationId): Collection
    {
        return $this->model->where('organization_id', $organizationId)->where('is_active', true)->get();
    }

    public function findByIdAndOrganization(int $id, int $organizationId): ?Model
    {
        return $this->model->where('id', $id)->where('organization_id', $organizationId)->first();
    }

    public function createForOrganization(int $organizationId, array $data): Model
    {
        return $this->model->create(array_merge($data, ['organization_id' => $organizationId]));
    }

    public function updateForOrganization(int $id, int $organizationId, array $data): ?Model
    {
        $model = $this->findByIdAndOrganization($id, $organizationId);

        if (!$model) {
            return null;
        }

        $model->update($data);

        return $model->fresh();
    }

    public function deleteForOrganization(int $id, int $organizationId): bool
    {
        $model = $this->findByIdAndOrganization($id, $organizationId);

        if (!$model) {
            return false;
        }

        return $model->delete();
    }

    public function toggleStatus(int $id): ?Model
    {
        $model = $this->findById($id);

        if (!$model) {
            return null;
        }

        $model->update(['is_active' => !$model->is_active]);

        return $model->fresh();
    }
}
