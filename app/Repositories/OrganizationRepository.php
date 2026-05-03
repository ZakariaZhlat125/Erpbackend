<?php

namespace App\Repositories;

use App\Models\Organization;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class OrganizationRepository extends BaseRepository implements OrganizationRepositoryInterface
{
    public function __construct(Organization $model)
    {
        parent::__construct($model);
    }

    public function findByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('status', $status)->paginate($perPage);
    }

    public function findByUserId(int $userId, array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->select($columns)->with($relations)->where('user_id', $userId)->get();
    }

    public function findWithCurrency(int $id): ?Model
    {
        return $this->model->with('baseCurrency')->find($id);
    }

    public function bulkCreate(array $organizationsData): Collection
    {
        $organizations = new Collection();

        foreach ($organizationsData as $data) {
            $organizations->push($this->create($data));
        }

        return $organizations;
    }

    public function forceDeleteById(int $id): bool
    {
        $model = $this->model->withTrashed()->find($id);

        if (!$model) {
            return false;
        }

        return $model->forceDelete();
    }

    public function toggleStatus(int $id): ?Model
    {
        $model = $this->findById($id);

        if (!$model) {
            return null;
        }

        $model->update(['status' => $model->status === 'active' ? 'inactive' : 'active']);

        return $model->fresh();
    }
}
