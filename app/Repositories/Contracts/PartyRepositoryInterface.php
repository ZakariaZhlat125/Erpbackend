<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface PartyRepositoryInterface extends RepositoryInterface
{
    public function findByOrganization(int $organizationId, int $perPage = 15): LengthAwarePaginator;

    public function findByRole(string $role, int $perPage = 15): LengthAwarePaginator;

    public function findWithRelations(int $id): ?Model;

    public function addRole(int $partyId, string $role): bool;

    public function addContact(int $partyId, array $contactData): Model;

    public function toggleStatus(int $id): ?Model;
}
