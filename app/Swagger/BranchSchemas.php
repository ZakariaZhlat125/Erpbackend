<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Branch",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Main Branch"),
        new OA\Property(property: "code", type: "string", example: "BR-001"),
        new OA\Property(property: "address", type: "string", nullable: true),
        new OA\Property(property: "phone", type: "string", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "BranchStoreRequest",
    required: ["name", "code"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "Main Branch"),
        new OA\Property(property: "code", type: "string", example: "BR-001"),
        new OA\Property(property: "address", type: "string", nullable: true),
        new OA\Property(property: "phone", type: "string", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
    ]
)]
#[OA\Schema(
    schema: "BranchUpdateRequest",
    properties: [
        new OA\Property(property: "name", type: "string", example: "Main Branch Updated"),
        new OA\Property(property: "code", type: "string", example: "BR-001"),
        new OA\Property(property: "address", type: "string", nullable: true),
        new OA\Property(property: "phone", type: "string", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", nullable: true),
        new OA\Property(property: "is_active", type: "boolean"),
    ]
)]
#[OA\Schema(
    schema: "BranchResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Branch"),
    ]
)]
#[OA\Schema(
    schema: "BranchListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Branch")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]

#[OA\Get(
    path: "/organizations/{organization}/branches",
    summary: "List branches for an organization",
    security: [["sanctum" => []]],
    tags: ["Branches"],
    parameters: [
        new OA\Parameter(name: "organization", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", default: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Paginated list of branches", content: new OA\JsonContent(ref: "#/components/schemas/BranchListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/organizations/{organization}/branches",
    summary: "Create a branch for an organization",
    security: [["sanctum" => []]],
    tags: ["Branches"],
    parameters: [
        new OA\Parameter(name: "organization", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/BranchStoreRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Branch created successfully", content: new OA\JsonContent(ref: "#/components/schemas/BranchResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/organizations/{organization}/branches/{branch}",
    summary: "Get a branch",
    security: [["sanctum" => []]],
    tags: ["Branches"],
    parameters: [
        new OA\Parameter(name: "organization", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "branch", in: "path", description: "Branch ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Branch details", content: new OA\JsonContent(ref: "#/components/schemas/BranchResponse")),
        new OA\Response(response: 404, description: "Branch not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/organizations/{organization}/branches/{branch}",
    summary: "Update a branch",
    security: [["sanctum" => []]],
    tags: ["Branches"],
    parameters: [
        new OA\Parameter(name: "organization", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "branch", in: "path", description: "Branch ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/BranchUpdateRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Branch updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/BranchResponse")),
        new OA\Response(response: 404, description: "Branch not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/organizations/{organization}/branches/{branch}",
    summary: "Delete a branch",
    security: [["sanctum" => []]],
    tags: ["Branches"],
    parameters: [
        new OA\Parameter(name: "organization", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "branch", in: "path", description: "Branch ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Branch deleted successfully"),
        new OA\Response(response: 404, description: "Branch not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/organizations/{organization}/branches/{branch}/toggle-status",
    summary: "Toggle branch active status",
    description: "Activate or deactivate a branch by toggling its is_active flag",
    security: [["sanctum" => []]],
    tags: ["Branches"],
    parameters: [
        new OA\Parameter(name: "organization", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "branch", in: "path", description: "Branch ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Status toggled successfully", content: new OA\JsonContent(ref: "#/components/schemas/BranchResponse")),
        new OA\Response(response: 404, description: "Branch not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class BranchSchemas
{
    // Branch schemas and endpoint documentation.
}
