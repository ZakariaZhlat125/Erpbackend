<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Party",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "code", type: "string", example: "PARTY-001"),
        new OA\Property(property: "type", type: "string", enum: ["individual", "company"], example: "company"),
        new OA\Property(property: "display_name", type: "string", example: "ABC Supplier"),
        new OA\Property(property: "legal_name", type: "string", nullable: true),
        new OA\Property(property: "tax_number", type: "string", nullable: true),
        new OA\Property(property: "currency_id", type: "integer", nullable: true),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string", enum: ["customer", "supplier", "agent", "contractor"])),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "PartyStoreRequest",
    required: ["code", "type", "display_name"],
    properties: [
        new OA\Property(property: "code", type: "string", example: "PARTY-001"),
        new OA\Property(property: "type", type: "string", enum: ["individual", "company"], example: "company"),
        new OA\Property(property: "display_name", type: "string", example: "ABC Supplier"),
        new OA\Property(property: "legal_name", type: "string", nullable: true),
        new OA\Property(property: "tax_number", type: "string", nullable: true),
        new OA\Property(property: "currency_id", type: "integer", nullable: true),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string", enum: ["customer", "supplier", "agent", "contractor"])),
    ]
)]
#[OA\Schema(
    schema: "PartyUpdateRequest",
    properties: [
        new OA\Property(property: "code", type: "string"),
        new OA\Property(property: "type", type: "string", enum: ["individual", "company"]),
        new OA\Property(property: "display_name", type: "string"),
        new OA\Property(property: "legal_name", type: "string", nullable: true),
        new OA\Property(property: "tax_number", type: "string", nullable: true),
        new OA\Property(property: "currency_id", type: "integer", nullable: true),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "is_active", type: "boolean"),
    ]
)]
#[OA\Schema(
    schema: "PartyResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Party"),
    ]
)]
#[OA\Schema(
    schema: "PartyListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Party")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "PartyContactRequest",
    required: ["name"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", nullable: true),
        new OA\Property(property: "phone", type: "string", nullable: true),
        new OA\Property(property: "position", type: "string", nullable: true),
        new OA\Property(property: "is_primary", type: "boolean", example: false),
    ]
)]
#[OA\Schema(
    schema: "PartyRoleRequest",
    required: ["role"],
    properties: [
        new OA\Property(property: "role", type: "string", enum: ["customer", "supplier", "agent", "contractor"], example: "customer"),
    ]
)]

#[OA\Get(
    path: "/parties",
    summary: "List parties",
    description: "Returns paginated list of parties with optional filters",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
        new OA\Parameter(name: "role", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["customer", "supplier", "agent", "contractor"])),
        new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Paginated list of parties", content: new OA\JsonContent(ref: "#/components/schemas/PartyListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/parties",
    summary: "Create a party",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/PartyStoreRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Party created successfully", content: new OA\JsonContent(ref: "#/components/schemas/PartyResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/parties/{id}",
    summary: "Get a party",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Party ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Party details", content: new OA\JsonContent(ref: "#/components/schemas/PartyResponse")),
        new OA\Response(response: 404, description: "Party not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/parties/{id}",
    summary: "Update a party",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Party ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/PartyUpdateRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "Party updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/PartyResponse")),
        new OA\Response(response: 404, description: "Party not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/parties/{id}",
    summary: "Delete a party",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Party ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Party deleted successfully"),
        new OA\Response(response: 404, description: "Party not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/parties/{id}/contacts",
    summary: "Add a contact to a party",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Party ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/PartyContactRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Contact added successfully"),
        new OA\Response(response: 404, description: "Party not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/parties/{id}/roles",
    summary: "Add a role to a party",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Party ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/PartyRoleRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "Role added successfully"),
        new OA\Response(response: 404, description: "Party not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/parties/statistics",
    summary: "Get party statistics",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    responses: [
        new OA\Response(response: 200, description: "Party statistics"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/parties/search",
    summary: "Search parties",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "q", in: "query", description: "Search term", required: true, schema: new OA\Schema(type: "string")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Search results", content: new OA\JsonContent(ref: "#/components/schemas/PartyListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/parties/{id}/toggle-status",
    summary: "Toggle party active status",
    description: "Activate or deactivate a party by toggling its is_active flag",
    security: [["sanctum" => []]],
    tags: ["Parties"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Party ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Status toggled successfully", content: new OA\JsonContent(ref: "#/components/schemas/PartyResponse")),
        new OA\Response(response: 404, description: "Party not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class PartySchemas
{
    // Party schemas and endpoint documentation.
}
