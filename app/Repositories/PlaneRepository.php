<?php

namespace App\Repositories;

use App\Models\Plane;
use App\Repositories\Contracts\PlaneRepositoryInterface;

class PlaneRepository extends BaseRepository implements PlaneRepositoryInterface
{
    public function __construct(Plane $model)
    {
        parent::__construct($model);
    }
}
