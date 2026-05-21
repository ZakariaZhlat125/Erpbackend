<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

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
#[OA\Schema(
    schema: "PlanResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Plan"),
    ]
)]
#[OA\Schema(
    schema: "PlanListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Plan")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]

// --- Admin Plan endpoints ---

#[OA\Get(
    path: "/admin/plans",
    summary: "Get all plans",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    parameters: [
        new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/PlanListResponse")),
        new OA\Response(response: 401, description: "Unauthorized"),
    ]
)]
#[OA\Post(
    path: "/admin/plans",
    summary: "Create a new plan",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/StorePlanRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Plan created", content: new OA\JsonContent(ref: "#/components/schemas/PlanResponse")),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Get(
    path: "/admin/plans/{id}",
    summary: "Get plan by ID",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/PlanResponse")),
        new OA\Response(response: 404, description: "Plan not found"),
    ]
)]
#[OA\Put(
    path: "/admin/plans/{id}",
    summary: "Update plan",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/UpdatePlanRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Plan updated", content: new OA\JsonContent(ref: "#/components/schemas/PlanResponse")),
        new OA\Response(response: 404, description: "Plan not found"),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Delete(
    path: "/admin/plans/{id}",
    summary: "Delete plan",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Plan deleted"),
        new OA\Response(response: 404, description: "Plan not found"),
    ]
)]
#[OA\Patch(
    path: "/admin/plans/{id}/change-status",
    summary: "Toggle plan active status",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Status updated", content: new OA\JsonContent(ref: "#/components/schemas/PlanResponse")),
        new OA\Response(response: 404, description: "Plan not found"),
    ]
)]
#[OA\Patch(
    path: "/admin/plans/{id}/toggle-popular",
    summary: "Toggle plan popular status",
    security: [["sanctum" => []]],
    tags: ["Admin/Plans"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Popular status updated", content: new OA\JsonContent(ref: "#/components/schemas/PlanResponse")),
        new OA\Response(response: 404, description: "Plan not found"),
    ]
)]

// --- Public Plan endpoints ---

#[OA\Get(
    path: "/plans/available",
    summary: "Get available plans",
    description: "Returns only active plans that can be subscribed to",
    security: [["sanctum" => []]],
    tags: ["Plans"],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/PlanListResponse")),
        new OA\Response(response: 401, description: "Unauthorized"),
    ]
)]
#[OA\Get(
    path: "/plans/{id}",
    summary: "Get plan details",
    description: "Get single active plan details",
    security: [["sanctum" => []]],
    tags: ["Plans"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/PlanResponse")),
        new OA\Response(response: 404, description: "Plan not found"),
    ]
)]
class PlanSchemas
{
    // Plan schemas and endpoint documentation.
}
