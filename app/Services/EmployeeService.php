<?php

namespace App\Services;

use App\Repositories\Contracts\EmployeeRepositoryInterface;

class EmployeeService extends BaseService
{
    public function __construct(EmployeeRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
