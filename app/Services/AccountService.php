<?php

namespace App\Services;

use App\Repositories\Contracts\AccountRepositoryInterface;

class AccountService extends BaseService
{
    public function __construct(AccountRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
