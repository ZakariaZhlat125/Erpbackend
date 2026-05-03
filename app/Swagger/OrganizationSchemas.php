<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Organization",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "user_id", type: "integer", example: 10),
        new OA\Property(property: "name", type: "string", example: "ABC Company"),
        new OA\Property(property: "legal_name", type: "string", example: "ABC Company LLC"),
        new OA\Property(property: "tax_number", type: "string", example: "123456789"),
        new OA\Property(property: "base_currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "timezone", type: "string", example: "UTC"),
        new OA\Property(property: "locale", type: "string", example: "en"),
        new OA\Property(property: "status", type: "string", enum: ["active", "suspended", "inactive"], example: "active"),
        new OA\Property(property: "address", type: "string", example: "123 Business Street", nullable: true),
        new OA\Property(property: "phone", type: "string", example: "+1234567890", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", example: "info@abccompany.com", nullable: true),
        new OA\Property(property: "website", type: "string", example: "https://abccompany.com", nullable: true),
        new OA\Property(property: "logo_path", type: "string", example: "logos/abc.png", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "OrganizationStoreRequest",
    required: ["name", "legal_name", "tax_number"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "ABC Company"),
        new OA\Property(property: "legal_name", type: "string", example: "ABC Company LLC"),
        new OA\Property(property: "tax_number", type: "string", example: "123456789"),
        new OA\Property(property: "base_currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "timezone", type: "string", example: "UTC", nullable: true),
        new OA\Property(property: "locale", type: "string", example: "en", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "suspended", "inactive"], example: "active", nullable: true),
        new OA\Property(property: "address", type: "string", example: "123 Business Street", nullable: true),
        new OA\Property(property: "phone", type: "string", example: "+1234567890", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", example: "info@abccompany.com", nullable: true),
        new OA\Property(property: "website", type: "string", example: "https://abccompany.com", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "OrganizationUpdateRequest",
    properties: [
        new OA\Property(property: "name", type: "string", example: "ABC Company Updated"),
        new OA\Property(property: "legal_name", type: "string", example: "ABC Company LLC"),
        new OA\Property(property: "tax_number", type: "string", example: "123456789"),
        new OA\Property(property: "base_currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "timezone", type: "string", example: "UTC", nullable: true),
        new OA\Property(property: "locale", type: "string", example: "en", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "suspended", "inactive"], example: "active", nullable: true),
        new OA\Property(property: "address", type: "string", example: "123 Business Street", nullable: true),
        new OA\Property(property: "phone", type: "string", example: "+1234567890", nullable: true),
        new OA\Property(property: "email", type: "string", format: "email", example: "info@abccompany.com", nullable: true),
        new OA\Property(property: "website", type: "string", example: "https://abccompany.com", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "OrganizationResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Organization"),
    ]
)]
#[OA\Schema(
    schema: "OrganizationListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Organization")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "OrganizationBulkStoreRequest",
    required: ["organizations"],
    properties: [
        new OA\Property(
            property: "organizations",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/OrganizationStoreRequest")
        ),
    ]
)]
#[OA\Schema(
    schema: "OrganizationBulkResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Organization")),
        new OA\Property(property: "message", type: "string", example: "Organizations created successfully"),
    ]
)]
#[OA\Get(
    path: "/organizations",
    summary: "List organizations",
    description: "Returns paginated list of organizations",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/OrganizationListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/organizations",
    summary: "Create new organization",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/OrganizationStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Organization created successfully", content: new OA\JsonContent(ref: "#/components/schemas/OrganizationResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/organizations/bulk",
    summary: "Create multiple organizations",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/OrganizationBulkStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Organizations created successfully", content: new OA\JsonContent(ref: "#/components/schemas/OrganizationBulkResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/organizations/{id}",
    summary: "Get organization by ID",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/OrganizationResponse")),
        new OA\Response(response: 404, description: "Organization not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/organizations/{id}",
    summary: "Update organization",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/OrganizationUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Organization updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/OrganizationResponse")),
        new OA\Response(response: 404, description: "Organization not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/organizations/{id}",
    summary: "Delete organization",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Organization deleted successfully"),
        new OA\Response(response: 404, description: "Organization not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/organizations/{id}/toggle-status",
    summary: "Toggle organization status",
    description: "Activate or deactivate an organization by toggling its status between active and inactive",
    security: [["sanctum" => []]],
    tags: ["Organizations"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Organization ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Status toggled successfully", content: new OA\JsonContent(ref: "#/components/schemas/OrganizationResponse")),
        new OA\Response(response: 404, description: "Organization not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class OrganizationSchemas
{
    // Organization schemas and endpoint documentation.
}
