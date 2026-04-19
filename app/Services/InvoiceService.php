<?php

namespace App\Services;

use App\Repositories\Contracts\InvoiceRepositoryInterface;

class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }
}
