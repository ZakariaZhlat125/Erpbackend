<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Payment",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "party_id", type: "integer", example: 1),
        new OA\Property(property: "invoice_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "number", type: "string", example: "PAY-2024-001"),
        new OA\Property(property: "direction", type: "string", enum: ["in", "out"], example: "in"),
        new OA\Property(property: "method", type: "string", enum: ["cash", "bank_transfer", "check", "credit_card", "other"], example: "bank_transfer"),
        new OA\Property(property: "amount", type: "number", format: "float", example: 500.00),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "paid_at", type: "string", format: "date-time", example: "2024-01-20T10:30:00Z"),
        new OA\Property(property: "reference", type: "string", example: "TXN-12345", nullable: true),
        new OA\Property(property: "notes", type: "string", example: "Partial payment for invoice INV-001", nullable: true),
        new OA\Property(property: "created_by", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "PaymentStoreRequest",
    required: ["party_id", "number", "direction", "method", "amount", "paid_at"],
    properties: [
        new OA\Property(property: "party_id", type: "integer", example: 1),
        new OA\Property(property: "invoice_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "number", type: "string", example: "PAY-2024-001"),
        new OA\Property(property: "direction", type: "string", enum: ["in", "out"], example: "in"),
        new OA\Property(property: "method", type: "string", enum: ["cash", "bank_transfer", "check", "credit_card", "other"], example: "bank_transfer"),
        new OA\Property(property: "amount", type: "number", format: "float", example: 500.00),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "paid_at", type: "string", format: "date-time", example: "2024-01-20T10:30:00Z"),
        new OA\Property(property: "reference", type: "string", example: "TXN-12345", nullable: true),
        new OA\Property(property: "notes", type: "string", example: "Partial payment for invoice INV-001", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "PaymentUpdateRequest",
    properties: [
        new OA\Property(property: "party_id", type: "integer", example: 1),
        new OA\Property(property: "invoice_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "number", type: "string", example: "PAY-2024-001"),
        new OA\Property(property: "direction", type: "string", enum: ["in", "out"], example: "in"),
        new OA\Property(property: "method", type: "string", enum: ["cash", "bank_transfer", "check", "credit_card", "other"], example: "bank_transfer"),
        new OA\Property(property: "amount", type: "number", format: "float", example: 500.00),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "paid_at", type: "string", format: "date-time", example: "2024-01-20T10:30:00Z"),
        new OA\Property(property: "reference", type: "string", example: "TXN-12345", nullable: true),
        new OA\Property(property: "notes", type: "string", example: "Partial payment for invoice INV-001", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "PaymentResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Payment"),
    ]
)]
#[OA\Schema(
    schema: "PaymentListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Payment")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Get(
    path: "/accounting/payments",
    summary: "List payments",
    description: "Returns paginated list of payments",
    security: [["sanctum" => []]],
    tags: ["Payments"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/PaymentListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/accounting/payments",
    summary: "Create new payment",
    security: [["sanctum" => []]],
    tags: ["Payments"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/PaymentStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Payment created successfully", content: new OA\JsonContent(ref: "#/components/schemas/PaymentResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/payments/{id}",
    summary: "Get payment by ID",
    security: [["sanctum" => []]],
    tags: ["Payments"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Payment ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/PaymentResponse")),
        new OA\Response(response: 404, description: "Payment not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/accounting/payments/{id}",
    summary: "Update payment",
    security: [["sanctum" => []]],
    tags: ["Payments"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Payment ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/PaymentUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Payment updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/PaymentResponse")),
        new OA\Response(response: 404, description: "Payment not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/accounting/payments/{id}",
    summary: "Delete payment",
    security: [["sanctum" => []]],
    tags: ["Payments"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Payment ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Payment deleted successfully"),
        new OA\Response(response: 404, description: "Payment not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class PaymentSchemas
{
    // Payment schemas and endpoint documentation.
}
