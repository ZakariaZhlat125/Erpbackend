<?php

namespace App\Http\Controllers\Api;

class SwaggerAnnotations
{
    public const SCHEMAS = [
        \App\Swagger\BaseSchemas::class,
        \App\Swagger\AuthSchemas::class,
        \App\Swagger\OrganizationSchemas::class,
        \App\Swagger\BranchSchemas::class,
        \App\Swagger\PartySchemas::class,
        \App\Swagger\UserSchemas::class,
        \App\Swagger\PlanSchemas::class,
        \App\Swagger\SubscriptionSchemas::class,
        \App\Swagger\AccountSchemas::class,
        \App\Swagger\AccountServiceSchemas::class,
        \App\Swagger\CurrencySchemas::class,
        \App\Swagger\EmployeeSchemas::class,
        \App\Swagger\InvoiceSchemas::class,
        \App\Swagger\PaymentSchemas::class,
        \App\Swagger\ProductSchemas::class,
        \App\Swagger\ProjectSchemas::class,
        \App\Swagger\TaskSchemas::class,
        \App\Swagger\WarehouseSchemas::class,
    ];
}
