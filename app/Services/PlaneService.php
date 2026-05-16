<?php

namespace App\Services;

use App\Repositories\Contracts\PlaneRepositoryInterface;

class PlaneService extends BaseService
{
    public function __construct(PlaneRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
