<?php

namespace App\Services;

use App\Repositories\Contracts\WarehouseRepositoryInterface;

class WarehouseService extends BaseService
{
    public function __construct(WarehouseRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
