<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AccountServiceModel",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Consulting Service"),
        new OA\Property(property: "description", type: "string", example: "Professional consulting service", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "AccountServiceStoreRequest",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Consulting Service"),
        new OA\Property(property: "description", type: "string", example: "Professional consulting service", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "AccountServiceUpdateRequest",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Consulting Service Updated"),
        new OA\Property(property: "description", type: "string", example: "Updated description", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "AccountServiceResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/AccountServiceModel"),
    ]
)]
#[OA\Schema(
    schema: "AccountServiceListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/AccountServiceModel")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Get(
    path: "/account_services",
    summary: "List account services",
    description: "Returns paginated list of account services",
    security: [["sanctum" => []]],
    tags: ["Account Services"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/AccountServiceListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/account_services",
    summary: "Create new account service",
    security: [["sanctum" => []]],
    tags: ["Account Services"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AccountServiceStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Account service created successfully", content: new OA\JsonContent(ref: "#/components/schemas/AccountServiceResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/account_services/{id}",
    summary: "Get account service by ID",
    security: [["sanctum" => []]],
    tags: ["Account Services"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account Service ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/AccountServiceResponse")),
        new OA\Response(response: 404, description: "Account service not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/account_services/{id}",
    summary: "Update account service",
    security: [["sanctum" => []]],
    tags: ["Account Services"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account Service ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AccountServiceUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Account service updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/AccountServiceResponse")),
        new OA\Response(response: 404, description: "Account service not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/account_services/{id}",
    summary: "Delete account service",
    security: [["sanctum" => []]],
    tags: ["Account Services"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account Service ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Account service deleted successfully"),
        new OA\Response(response: 404, description: "Account service not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class AccountServiceSchemas
{
    // Account service schemas and endpoint documentation.
}
