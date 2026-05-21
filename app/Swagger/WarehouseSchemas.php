<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Warehouse",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "name", type: "string", example: "Main Warehouse"),
        new OA\Property(property: "code", type: "string", example: "WH-001"),
        new OA\Property(property: "address", type: "string", example: "123 Storage Blvd", nullable: true),
        new OA\Property(property: "manager_user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "allow_negative_stock", type: "boolean", example: false),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "WarehouseStoreRequest",
    required: ["name", "code"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "name", type: "string", example: "Main Warehouse"),
        new OA\Property(property: "code", type: "string", example: "WH-001"),
        new OA\Property(property: "address", type: "string", example: "123 Storage Blvd", nullable: true),
        new OA\Property(property: "manager_user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "allow_negative_stock", type: "boolean", example: false, nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "WarehouseUpdateRequest",
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "name", type: "string", example: "Main Warehouse Updated"),
        new OA\Property(property: "code", type: "string", example: "WH-001"),
        new OA\Property(property: "address", type: "string", example: "456 Storage Ave", nullable: true),
        new OA\Property(property: "manager_user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "allow_negative_stock", type: "boolean", example: false, nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "WarehouseResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Warehouse"),
    ]
)]
#[OA\Schema(
    schema: "WarehouseListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Warehouse")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Get(
    path: "/inventory/warehouses",
    summary: "List warehouses",
    description: "Returns paginated list of warehouses",
    security: [["sanctum" => []]],
    tags: ["Warehouses"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/WarehouseListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/inventory/warehouses",
    summary: "Create new warehouse",
    security: [["sanctum" => []]],
    tags: ["Warehouses"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/WarehouseStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Warehouse created successfully", content: new OA\JsonContent(ref: "#/components/schemas/WarehouseResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/inventory/warehouses/{id}",
    summary: "Get warehouse by ID",
    security: [["sanctum" => []]],
    tags: ["Warehouses"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Warehouse ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/WarehouseResponse")),
        new OA\Response(response: 404, description: "Warehouse not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/inventory/warehouses/{id}",
    summary: "Update warehouse",
    security: [["sanctum" => []]],
    tags: ["Warehouses"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Warehouse ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/WarehouseUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Warehouse updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/WarehouseResponse")),
        new OA\Response(response: 404, description: "Warehouse not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/inventory/warehouses/{id}",
    summary: "Delete warehouse",
    security: [["sanctum" => []]],
    tags: ["Warehouses"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Warehouse ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Warehouse deleted successfully"),
        new OA\Response(response: 404, description: "Warehouse not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class WarehouseSchemas
{
    // Warehouse schemas and endpoint documentation.
}
