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
    ];
}
