<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Account",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "code", type: "string", example: "1000"),
        new OA\Property(property: "name", type: "string", example: "Cash"),
        new OA\Property(property: "type", type: "string", enum: ["asset", "liability", "equity", "revenue", "expense"], example: "asset"),
        new OA\Property(property: "parent_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "level", type: "integer", example: 1),
        new OA\Property(property: "allow_manual_entries", type: "boolean", example: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "AccountStoreRequest",
    required: ["code", "name", "type"],
    properties: [
        new OA\Property(property: "code", type: "string", example: "1000"),
        new OA\Property(property: "name", type: "string", example: "Cash"),
        new OA\Property(property: "type", type: "string", enum: ["asset", "liability", "equity", "revenue", "expense"], example: "asset"),
        new OA\Property(property: "parent_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "level", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "allow_manual_entries", type: "boolean", example: true, nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "AccountUpdateRequest",
    properties: [
        new OA\Property(property: "code", type: "string", example: "1000"),
        new OA\Property(property: "name", type: "string", example: "Cash Updated"),
        new OA\Property(property: "type", type: "string", enum: ["asset", "liability", "equity", "revenue", "expense"], example: "asset"),
        new OA\Property(property: "parent_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "level", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "allow_manual_entries", type: "boolean", example: true, nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "AccountResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Account"),
    ]
)]
#[OA\Schema(
    schema: "AccountListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Account")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "AccountStatementResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(
            property: "data",
            type: "object",
            properties: [
                new OA\Property(property: "account", ref: "#/components/schemas/Account"),
                new OA\Property(property: "transactions", type: "array", items: new OA\Items(type: "object")),
                new OA\Property(property: "opening_balance", type: "number", format: "float", example: 0),
                new OA\Property(property: "closing_balance", type: "number", format: "float", example: 0),
            ]
        ),
    ]
)]
#[OA\Get(
    path: "/accounting/accounts",
    summary: "List accounts",
    description: "Returns paginated list of chart of accounts",
    security: [["sanctum" => []]],
    tags: ["Accounts"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/AccountListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/accounting/accounts",
    summary: "Create new account",
    security: [["sanctum" => []]],
    tags: ["Accounts"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AccountStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Account created successfully", content: new OA\JsonContent(ref: "#/components/schemas/AccountResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/accounts/{id}",
    summary: "Get account by ID",
    security: [["sanctum" => []]],
    tags: ["Accounts"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/AccountResponse")),
        new OA\Response(response: 404, description: "Account not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/accounting/accounts/{id}",
    summary: "Update account",
    security: [["sanctum" => []]],
    tags: ["Accounts"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AccountUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Account updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/AccountResponse")),
        new OA\Response(response: 404, description: "Account not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/accounting/accounts/{id}",
    summary: "Delete account",
    security: [["sanctum" => []]],
    tags: ["Accounts"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Account deleted successfully"),
        new OA\Response(response: 404, description: "Account not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/accounts/{id}/statement",
    summary: "Get account statement",
    description: "Retrieve account ledger with transactions, opening and closing balances",
    security: [["sanctum" => []]],
    tags: ["Accounts"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Account ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Account statement retrieved", content: new OA\JsonContent(ref: "#/components/schemas/AccountStatementResponse")),
        new OA\Response(response: 404, description: "Account not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class AccountSchemas
{
    // Account schemas and endpoint documentation.
}
