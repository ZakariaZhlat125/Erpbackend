<?php

namespace App\Services;

use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentService extends BaseService
{
    public function __construct(PaymentRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
