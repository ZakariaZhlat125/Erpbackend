<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Invoice",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "number", type: "string", example: "INV-2024-001"),
        new OA\Property(property: "type", type: "string", enum: ["sale", "purchase"], example: "sale"),
        new OA\Property(property: "party_id", type: "integer", example: 1),
        new OA\Property(property: "status", type: "string", enum: ["draft", "approved", "partially_paid", "paid", "cancelled"], example: "draft"),
        new OA\Property(property: "issue_date", type: "string", format: "date", example: "2024-01-15"),
        new OA\Property(property: "due_date", type: "string", format: "date", example: "2024-02-15"),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "subtotal", type: "number", format: "float", example: 1000.00),
        new OA\Property(property: "discount_total", type: "number", format: "float", example: 50.00),
        new OA\Property(property: "tax_total", type: "number", format: "float", example: 142.50),
        new OA\Property(property: "grand_total", type: "number", format: "float", example: 1092.50),
        new OA\Property(property: "payment_term_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "notes", type: "string", example: "Payment due within 30 days", nullable: true),
        new OA\Property(property: "created_by", type: "integer", example: 1),
        new OA\Property(property: "approved_by", type: "integer", example: null, nullable: true),
        new OA\Property(property: "approved_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "InvoiceStoreRequest",
    required: ["number", "type", "party_id", "issue_date", "due_date"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "number", type: "string", example: "INV-2024-001"),
        new OA\Property(property: "type", type: "string", enum: ["sale", "purchase"], example: "sale"),
        new OA\Property(property: "party_id", type: "integer", example: 1),
        new OA\Property(property: "status", type: "string", enum: ["draft", "approved", "partially_paid", "paid", "cancelled"], example: "draft", nullable: true),
        new OA\Property(property: "issue_date", type: "string", format: "date", example: "2024-01-15"),
        new OA\Property(property: "due_date", type: "string", format: "date", example: "2024-02-15"),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "subtotal", type: "number", format: "float", example: 1000.00, nullable: true),
        new OA\Property(property: "discount_total", type: "number", format: "float", example: 50.00, nullable: true),
        new OA\Property(property: "tax_total", type: "number", format: "float", example: 142.50, nullable: true),
        new OA\Property(property: "grand_total", type: "number", format: "float", example: 1092.50, nullable: true),
        new OA\Property(property: "payment_term_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "notes", type: "string", example: "Payment due within 30 days", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "InvoiceUpdateRequest",
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "number", type: "string", example: "INV-2024-001"),
        new OA\Property(property: "type", type: "string", enum: ["sale", "purchase"], example: "sale"),
        new OA\Property(property: "party_id", type: "integer", example: 1),
        new OA\Property(property: "status", type: "string", enum: ["draft", "approved", "partially_paid", "paid", "cancelled"], example: "draft"),
        new OA\Property(property: "issue_date", type: "string", format: "date", example: "2024-01-15"),
        new OA\Property(property: "due_date", type: "string", format: "date", example: "2024-02-15"),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "subtotal", type: "number", format: "float", example: 1000.00),
        new OA\Property(property: "discount_total", type: "number", format: "float", example: 50.00),
        new OA\Property(property: "tax_total", type: "number", format: "float", example: 142.50),
        new OA\Property(property: "grand_total", type: "number", format: "float", example: 1092.50),
        new OA\Property(property: "payment_term_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "notes", type: "string", example: "Payment due within 30 days", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "InvoiceResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Invoice"),
    ]
)]
#[OA\Schema(
    schema: "InvoiceListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Invoice")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "InvoiceBulkApproveRequest",
    required: ["invoice_ids"],
    properties: [
        new OA\Property(property: "invoice_ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
    ]
)]
#[OA\Schema(
    schema: "InvoiceBulkDeleteRequest",
    required: ["invoice_ids"],
    properties: [
        new OA\Property(property: "invoice_ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
    ]
)]
#[OA\Get(
    path: "/accounting/invoices",
    summary: "List invoices",
    description: "Returns paginated list of invoices",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/accounting/invoices",
    summary: "Create new invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/InvoiceStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Invoice created successfully", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/invoices/statistics",
    summary: "Get invoice statistics",
    description: "Retrieve aggregated invoice statistics, optionally filtered by type",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "type", in: "query", description: "Invoice type filter", required: false, schema: new OA\Schema(type: "string", enum: ["sale", "purchase"])),
    ],
    responses: [
        new OA\Response(response: 200, description: "Statistics retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/invoices/search",
    summary: "Search invoices",
    description: "Search invoices by various criteria",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "number_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["sale", "purchase"])),
        new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["draft", "approved", "partially_paid", "paid", "cancelled"])),
        new OA\Parameter(name: "party_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "issue_date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "issue_date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "due_date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "due_date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "grand_total_from", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "grand_total_to", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Search results", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/invoices/export",
    summary: "Export invoices",
    description: "Export invoices data to Excel",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    responses: [
        new OA\Response(response: 200, description: "Export file download"),
        new OA\Response(response: 501, description: "Not implemented"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/accounting/invoices/bulk-approve",
    summary: "Bulk approve invoices",
    description: "Approve multiple invoices at once",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/InvoiceBulkApproveRequest")),
    responses: [
        new OA\Response(response: 200, description: "Bulk approval completed", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/accounting/invoices/bulk-delete",
    summary: "Bulk delete invoices",
    description: "Delete multiple draft invoices at once",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/InvoiceBulkDeleteRequest")),
    responses: [
        new OA\Response(response: 200, description: "Bulk delete completed", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/invoices/{id}",
    summary: "Get invoice by ID",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceResponse")),
        new OA\Response(response: 404, description: "Invoice not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/accounting/invoices/{id}",
    summary: "Update invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/InvoiceUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Invoice updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceResponse")),
        new OA\Response(response: 404, description: "Invoice not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/accounting/invoices/{id}",
    summary: "Delete invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Invoice deleted successfully"),
        new OA\Response(response: 404, description: "Invoice not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/accounting/invoices/{id}/approve",
    summary: "Approve invoice",
    description: "Approve a draft invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Invoice approved successfully", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceResponse")),
        new OA\Response(response: 404, description: "Invoice not found"),
        new OA\Response(response: 409, description: "Only draft invoices can be approved"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/accounting/invoices/{id}/cancel",
    summary: "Cancel invoice",
    description: "Cancel an invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Invoice cancelled successfully", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceResponse")),
        new OA\Response(response: 404, description: "Invoice not found"),
        new OA\Response(response: 409, description: "Invoice is already cancelled"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/accounting/invoices/{id}/duplicate",
    summary: "Duplicate invoice",
    description: "Create a copy of an existing invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 201, description: "Invoice duplicated successfully", content: new OA\JsonContent(ref: "#/components/schemas/InvoiceResponse")),
        new OA\Response(response: 400, description: "Bad request"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/accounting/invoices/{id}/pdf",
    summary: "Download invoice PDF",
    description: "Generate and download PDF for an invoice",
    security: [["sanctum" => []]],
    tags: ["Invoices"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Invoice ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "PDF file stream", content: new OA\MediaType(mediaType: "application/pdf")),
        new OA\Response(response: 404, description: "Invoice not found"),
        new OA\Response(response: 501, description: "Not implemented"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class InvoiceSchemas
{
    // Invoice schemas and endpoint documentation.
}
