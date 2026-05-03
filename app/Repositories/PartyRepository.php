<?php

namespace App\Repositories;

use App\Models\Party;
use App\Models\PartyContact;
use App\Repositories\Contracts\PartyRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class PartyRepository extends BaseRepository implements PartyRepositoryInterface
{
    public function __construct(Party $model)
    {
        parent::__construct($model);
    }

    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->withoutGlobalScopes()
            ->where('organization_id', $organizationId)
            ->paginate($perPage);
    }

    public function findByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->whereHas('roles', fn($q) => $q->where('role', $role))
            ->paginate($perPage);
    }

    public function findWithRelations(int $id): ?Model
    {
        return $this->model->with(['roles', 'contacts', 'addresses', 'currency'])->find($id);
    }

    public function addRole(int $partyId, string $role): bool
    {
        $party = $this->findById($partyId);

        if (!$party) {
            return false;
        }

        $party->roles()->firstOrCreate(['role' => $role]);

        return true;
    }

    public function addContact(int $partyId, array $contactData): Model
    {
        $party = $this->findById($partyId);

        return $party->contacts()->create($contactData);
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
