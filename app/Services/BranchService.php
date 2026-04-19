<?php

namespace App\Services;

use App\Repositories\Contracts\BranchRepositoryInterface;

class BranchService extends BaseService
{
    public function __construct(BranchRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
