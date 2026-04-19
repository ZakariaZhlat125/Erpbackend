<?php

namespace App\Services;

use App\Repositories\Contracts\PartyRepositoryInterface;

class PartyService extends BaseService
{
    public function __construct(PartyRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
