<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Product",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "sku", type: "string", example: "PRD-001"),
        new OA\Property(property: "type", type: "string", enum: ["product", "service"], example: "product"),
        new OA\Property(property: "name", type: "string", example: "Office Chair"),
        new OA\Property(property: "description", type: "string", example: "Ergonomic office chair", nullable: true),
        new OA\Property(property: "category_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "unit_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "cost_price", type: "number", format: "float", example: 150.00),
        new OA\Property(property: "selling_price", type: "number", format: "float", example: 250.00),
        new OA\Property(property: "tax_rate_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "track_inventory", type: "boolean", example: true),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "image_path", type: "string", example: "products/chair.jpg", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "ProductStoreRequest",
    required: ["sku", "type", "name", "cost_price", "selling_price"],
    properties: [
        new OA\Property(property: "sku", type: "string", example: "PRD-001"),
        new OA\Property(property: "type", type: "string", enum: ["product", "service"], example: "product"),
        new OA\Property(property: "name", type: "string", example: "Office Chair"),
        new OA\Property(property: "description", type: "string", example: "Ergonomic office chair", nullable: true),
        new OA\Property(property: "category_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "unit_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "cost_price", type: "number", format: "float", example: 150.00),
        new OA\Property(property: "selling_price", type: "number", format: "float", example: 250.00),
        new OA\Property(property: "tax_rate_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "track_inventory", type: "boolean", example: true, nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
        new OA\Property(property: "image_path", type: "string", example: "products/chair.jpg", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "ProductUpdateRequest",
    properties: [
        new OA\Property(property: "sku", type: "string", example: "PRD-001"),
        new OA\Property(property: "type", type: "string", enum: ["product", "service"], example: "product"),
        new OA\Property(property: "name", type: "string", example: "Office Chair Updated"),
        new OA\Property(property: "description", type: "string", example: "Updated ergonomic office chair", nullable: true),
        new OA\Property(property: "category_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "unit_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "cost_price", type: "number", format: "float", example: 160.00),
        new OA\Property(property: "selling_price", type: "number", format: "float", example: 260.00),
        new OA\Property(property: "tax_rate_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "track_inventory", type: "boolean", example: true, nullable: true),
        new OA\Property(property: "is_active", type: "boolean", example: true, nullable: true),
        new OA\Property(property: "image_path", type: "string", example: "products/chair.jpg", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "ProductResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Product"),
    ]
)]
#[OA\Schema(
    schema: "ProductListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Product")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "ProductBulkUpdatePricesRequest",
    required: ["product_ids"],
    properties: [
        new OA\Property(property: "product_ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
        new OA\Property(property: "cost_price", type: "number", format: "float", example: 160.00, nullable: true),
        new OA\Property(property: "selling_price", type: "number", format: "float", example: 260.00, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "ProductBulkActivateRequest",
    required: ["product_ids", "is_active"],
    properties: [
        new OA\Property(property: "product_ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
        new OA\Property(property: "is_active", type: "boolean", example: true),
    ]
)]
#[OA\Get(
    path: "/inventory/products",
    summary: "List products",
    description: "Returns paginated list of products",
    security: [["sanctum" => []]],
    tags: ["Products"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/ProductListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/inventory/products",
    summary: "Create new product",
    security: [["sanctum" => []]],
    tags: ["Products"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProductStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Product created successfully", content: new OA\JsonContent(ref: "#/components/schemas/ProductResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/inventory/products/statistics",
    summary: "Get product statistics",
    description: "Retrieve aggregated product statistics",
    security: [["sanctum" => []]],
    tags: ["Products"],
    responses: [
        new OA\Response(response: 200, description: "Statistics retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/inventory/products/search",
    summary: "Search products",
    description: "Search products by various criteria",
    security: [["sanctum" => []]],
    tags: ["Products"],
    parameters: [
        new OA\Parameter(name: "sku_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "name_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "description_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["product", "service"])),
        new OA\Parameter(name: "category_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean")),
        new OA\Parameter(name: "track_inventory", in: "query", required: false, schema: new OA\Schema(type: "boolean")),
        new OA\Parameter(name: "cost_price_from", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "cost_price_to", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "selling_price_from", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "selling_price_to", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Search results", content: new OA\JsonContent(ref: "#/components/schemas/ProductListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/inventory/products/low-stock",
    summary: "Get low stock products",
    description: "Returns products with stock below the specified threshold",
    security: [["sanctum" => []]],
    tags: ["Products"],
    parameters: [
        new OA\Parameter(name: "threshold", in: "query", description: "Stock threshold", required: false, schema: new OA\Schema(type: "integer", example: 10)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Low stock products retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/inventory/products/export",
    summary: "Export products",
    description: "Export products data to Excel",
    security: [["sanctum" => []]],
    tags: ["Products"],
    responses: [
        new OA\Response(response: 200, description: "Export file download"),
        new OA\Response(response: 501, description: "Not implemented"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/inventory/products/bulk-update-prices",
    summary: "Bulk update product prices",
    description: "Update prices for multiple products at once",
    security: [["sanctum" => []]],
    tags: ["Products"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProductBulkUpdatePricesRequest")),
    responses: [
        new OA\Response(response: 200, description: "Prices updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/inventory/products/bulk-activate",
    summary: "Bulk activate/deactivate products",
    description: "Activate or deactivate multiple products at once",
    security: [["sanctum" => []]],
    tags: ["Products"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProductBulkActivateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Products status updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/inventory/products/import",
    summary: "Import products",
    description: "Import products from an Excel file",
    security: [["sanctum" => []]],
    tags: ["Products"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: "multipart/form-data",
            schema: new OA\Schema(
                properties: [
                    new OA\Property(property: "file", type: "string", format: "binary"),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 200, description: "Import queued", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/inventory/products/{id}",
    summary: "Get product by ID",
    security: [["sanctum" => []]],
    tags: ["Products"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Product ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/ProductResponse")),
        new OA\Response(response: 404, description: "Product not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/inventory/products/{id}",
    summary: "Update product",
    security: [["sanctum" => []]],
    tags: ["Products"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Product ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProductUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Product updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/ProductResponse")),
        new OA\Response(response: 404, description: "Product not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/inventory/products/{id}",
    summary: "Delete product",
    security: [["sanctum" => []]],
    tags: ["Products"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Product ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Product deleted successfully"),
        new OA\Response(response: 404, description: "Product not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class ProductSchemas
{
    // Product schemas and endpoint documentation.
}
