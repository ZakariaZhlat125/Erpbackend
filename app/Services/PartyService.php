<?php

namespace App\Services;

use App\Models\Party;
use App\Repositories\Contracts\PartyRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class PartyService extends BaseService
{
    public function __construct(
        protected PartyRepositoryInterface $partyRepository
    ) {
        parent::__construct($partyRepository);
    }

    public function getPaginated(int $perPage = 15, array $columns = ['*'], array $relations = [], array $filters = []): LengthAwarePaginator
    {
        return $this->partyRepository->paginate($perPage, $columns, $relations, $filters);
    }

    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->partyRepository->findByOrganization($organizationId, $perPage);
    }

    public function findByRole(string $role, int $perPage = 15): LengthAwarePaginator
    {
        return $this->partyRepository->findByRole($role, $perPage);
    }

    public function findWithRelations(int $id): ?Model
    {
        return $this->partyRepository->findWithRelations($id);
    }

    public function addRole(int $partyId, string $role): bool
    {
        return $this->partyRepository->addRole($partyId, $role);
    }

    public function addContact(int $partyId, array $contactData): Model
    {
        return $this->partyRepository->addContact($partyId, $contactData);
    }

    public function toggleStatus(int $id): ?Model
    {
        return $this->partyRepository->toggleStatus($id);
    }

    public function getStatistics(?int $organizationId = null): array
    {
        $query = Party::query();

        if ($organizationId) {
            $query->where('organization_id', $organizationId);
        }

        return [
            'total_count'     => (clone $query)->count(),
            'active_count'    => (clone $query)->where('is_active', true)->count(),
            'inactive_count'  => (clone $query)->where('is_active', false)->count(),
            'individual_count' => (clone $query)->where('type', 'individual')->count(),
            'company_count'   => (clone $query)->where('type', 'company')->count(),
        ];
    }
}
