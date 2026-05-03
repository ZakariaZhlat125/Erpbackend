<?php

namespace App\Repositories;

use App\Models\AccountService;
use App\Repositories\Contracts\AccountServiceRepositoryInterface;

class AccountServiceRepository extends BaseRepository implements AccountServiceRepositoryInterface
{
    public function __construct(AccountService $model)
    {
        parent::__construct($model);
    }
}
