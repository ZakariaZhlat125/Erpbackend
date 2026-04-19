<?php

namespace App\Repositories;

use App\Models\Party;
use App\Repositories\Contracts\PartyRepositoryInterface;

class PartyRepository extends BaseRepository implements PartyRepositoryInterface
{
    public function __construct(Party $model)
    {
        parent::__construct($model);
    }
}
