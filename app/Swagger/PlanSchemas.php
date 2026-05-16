<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin/Plans", description: "Admin plan management endpoints")]
#[OA\Schema(
    schema: "Plan",
    required: ["id", "name", "price", "billing_cycle"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Premium Plan"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Full access to all features"),
        new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
        new OA\Property(property: "billing_cycle", type: "string", enum: ["monthly", "yearly", "lifetime"], example: "monthly"),
        new OA\Property(property: "max_users", type: "integer", nullable: true, example: 50),
        new OA\Property(property: "max_branches", type: "integer", nullable: true, example: 10),
        new OA\Property(
            property: "features",
            type: "array",
            items: new OA\Items(type: "string"),
            nullable: true,
            example: ["Advanced Reporting", "API Access", "Priority Support"]
        ),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "is_popular", type: "boolean", example: false),
        new OA\Property(property: "sort_order", type: "integer", example: 0),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "StorePlanRequest",
    required: ["name", "price", "billing_cycle"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Premium Plan"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Full access to all features"),
        new OA\Property(property: "price", type: "number", format: "float", example: 99.99),
        new OA\Property(property: "billing_cycle", type: "string", enum: ["monthly", "yearly", "lifetime"], example: "monthly"),
        new OA\Property(property: "max_users", type: "integer", nullable: true, example: 50),
        new OA\Property(property: "max_branches", type: "integer", nullable: true, example: 10),
        new OA\Property(
            property: "features",
            type: "array",
            items: new OA\Items(type: "string"),
            nullable: true,
            example: ["Advanced Reporting", "API Access"]
        ),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "is_popular", type: "boolean", example: false),
        new OA\Property(property: "sort_order", type: "integer", example: 0),
    ]
)]
#[OA\Schema(
    schema: "UpdatePlanRequest",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Premium Plan"),
        new OA\Property(property: "description", type: "string", nullable: true, example: "Updated description"),
        new OA\Property(property: "price", type: "number", format: "float", example: 89.99),
        new OA\Property(property: "billing_cycle", type: "string", enum: ["monthly", "yearly", "lifetime"], example: "yearly"),
        new OA\Property(property: "max_users", type: "integer", nullable: true, example: 100),
        new OA\Property(property: "max_branches", type: "integer", nullable: true, example: 20),
        new OA\Property(
            property: "features",
            type: "array",
            items: new OA\Items(type: "string"),
            nullable: true,
            example: ["All Features"]
        ),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "is_popular", type: "boolean", example: true),
        new OA\Property(property: "sort_order", type: "integer", example: 1),
    ]
)]
class PlanSchemas
{
}
