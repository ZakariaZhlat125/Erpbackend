<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Currency",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "code", type: "string", example: "USD"),
        new OA\Property(property: "name", type: "string", example: "US Dollar"),
        new OA\Property(property: "symbol", type: "string", example: "$"),
        new OA\Property(property: "decimal_separator", type: "string", example: "."),
        new OA\Property(property: "thousands_separator", type: "string", example: ","),
        new OA\Property(property: "decimal_places", type: "integer", example: 2),
        new OA\Property(property: "exchange_rate", type: "number", format: "float", example: 1.000000),
        new OA\Property(property: "is_base", type: "boolean", example: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "CurrencyStoreRequest",
    required: ["code", "name", "symbol", "exchange_rate"],
    properties: [
        new OA\Property(property: "code", type: "string", example: "USD", description: "3-letter currency code"),
        new OA\Property(property: "name", type: "string", example: "US Dollar"),
        new OA\Property(property: "symbol", type: "string", example: "$"),
        new OA\Property(property: "decimal_separator", type: "string", example: ".", nullable: true),
        new OA\Property(property: "thousands_separator", type: "string", example: ",", nullable: true),
        new OA\Property(property: "decimal_places", type: "integer", example: 2, nullable: true),
        new OA\Property(property: "exchange_rate", type: "number", format: "float", example: 1.000000),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "CurrencyUpdateRequest",
    properties: [
        new OA\Property(property: "code", type: "string", example: "USD"),
        new OA\Property(property: "name", type: "string", example: "US Dollar"),
        new OA\Property(property: "symbol", type: "string", example: "$"),
        new OA\Property(property: "decimal_separator", type: "string", example: ".", nullable: true),
        new OA\Property(property: "thousands_separator", type: "string", example: ",", nullable: true),
        new OA\Property(property: "decimal_places", type: "integer", example: 2, nullable: true),
        new OA\Property(property: "exchange_rate", type: "number", format: "float", example: 1.000000),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "CurrencyResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Currency"),
    ]
)]
#[OA\Schema(
    schema: "CurrencyListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Currency")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "CurrencyConvertRequest",
    required: ["from_currency_id", "to_currency_id", "amount"],
    properties: [
        new OA\Property(property: "from_currency_id", type: "integer", example: 1),
        new OA\Property(property: "to_currency_id", type: "integer", example: 2),
        new OA\Property(property: "amount", type: "number", format: "float", example: 100.00),
    ]
)]
#[OA\Schema(
    schema: "CurrencyConvertResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Success"),
        new OA\Property(
            property: "data",
            type: "object",
            properties: [
                new OA\Property(property: "original_amount", type: "number", format: "float", example: 100.00),
                new OA\Property(property: "converted_amount", type: "number", format: "float", example: 85.50),
                new OA\Property(property: "from_currency_id", type: "integer", example: 1),
                new OA\Property(property: "to_currency_id", type: "integer", example: 2),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: "CurrencyUpdateRateRequest",
    required: ["exchange_rate"],
    properties: [
        new OA\Property(property: "exchange_rate", type: "number", format: "float", example: 1.350000),
    ]
)]
#[OA\Get(
    path: "/currencies",
    summary: "List currencies",
    description: "Returns paginated list of currencies. Pass active_only query parameter to get only active currencies.",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
        new OA\Parameter(name: "active_only", in: "query", description: "Return only active currencies", required: false, schema: new OA\Schema(type: "boolean")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/CurrencyListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/currencies",
    summary: "Create new currency",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/CurrencyStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Currency created successfully", content: new OA\JsonContent(ref: "#/components/schemas/CurrencyResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/currencies/active",
    summary: "Get active currencies",
    description: "Returns list of all active currencies",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Currency")),
            ]
        )),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/currencies/base",
    summary: "Get base currency",
    description: "Returns the base currency for the organization",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/CurrencyResponse")),
        new OA\Response(response: 404, description: "No base currency set"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/currencies/convert",
    summary: "Convert currency amount",
    description: "Convert an amount from one currency to another",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/CurrencyConvertRequest")),
    responses: [
        new OA\Response(response: 200, description: "Conversion successful", content: new OA\JsonContent(ref: "#/components/schemas/CurrencyConvertResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/currencies/{id}",
    summary: "Get currency by ID",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Currency ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/CurrencyResponse")),
        new OA\Response(response: 404, description: "Currency not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/currencies/{id}",
    summary: "Update currency",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Currency ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/CurrencyUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Currency updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/CurrencyResponse")),
        new OA\Response(response: 404, description: "Currency not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/currencies/{id}",
    summary: "Delete currency",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Currency ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Currency deleted successfully"),
        new OA\Response(response: 404, description: "Currency not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/currencies/{id}/set-base",
    summary: "Set currency as base",
    description: "Set the specified currency as the base currency",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Currency ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Base currency updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 404, description: "Currency not found"),
        new OA\Response(response: 400, description: "Bad request"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/currencies/{id}/update-rate",
    summary: "Update exchange rate",
    description: "Update the exchange rate for the specified currency",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Currency ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/CurrencyUpdateRateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Exchange rate updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 404, description: "Currency not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/currencies/{id}/toggle-active",
    summary: "Toggle currency active status",
    description: "Activate or deactivate a currency",
    security: [["sanctum" => []]],
    tags: ["Currencies"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Currency ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Currency status toggled successfully", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 404, description: "Currency not found"),
        new OA\Response(response: 400, description: "Bad request"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class CurrencySchemas
{
    // Currency schemas and endpoint documentation.
}
