<?php

namespace App\Repositories\Contracts;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ActivityLogRepositoryInterface extends RepositoryInterface
{
    public function getForOrganization(int $organizationId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    
    public function getBySubject(string $subjectType, int $subjectId, int $perPage = 15): LengthAwarePaginator;
    
    public function getByActor(int $actorId, int $perPage = 15): LengthAwarePaginator;
    
    public function getSensitiveLogs(int $organizationId, int $perPage = 15): LengthAwarePaginator;
    
    public function getStatistics(int $organizationId, ?string $from = null, ?string $to = null): array;
}
