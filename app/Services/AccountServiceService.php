<?php

namespace App\Services;

use App\Repositories\Contracts\AccountServiceRepositoryInterface;

class AccountServiceService extends BaseService
{
    public function __construct(AccountServiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
