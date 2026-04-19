<?php

namespace App\Services;

use App\Repositories\Contracts\ProjectRepositoryInterface;

class ProjectService extends BaseService
{
    public function __construct(ProjectRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
