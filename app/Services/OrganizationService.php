<?php

namespace App\Services;

use App\Repositories\Contracts\OrganizationRepositoryInterface;

class OrganizationService extends BaseService
{
    public function __construct(OrganizationRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
