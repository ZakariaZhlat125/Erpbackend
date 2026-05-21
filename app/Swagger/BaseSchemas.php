<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    description: "Comprehensive ERP System API Documentation with Multi-tenancy and Role-based Access Control",
    title: "ERP V2 API",
)]
#[OA\Server(
    url: "http://localhost:8000/api/v1",
    description: "Local Development Server"
)]
#[OA\Server(
    url: "https://api.example.com/api/v1",
    description: "Production Server"
)]
#[OA\SecurityScheme(
    securityScheme: "sanctum",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT",
    description: "Enter token in format: Bearer {your_token}"
)]
#[OA\Tag(name: "Authentication", description: "User authentication and authorization")]
#[OA\Tag(name: "Organizations", description: "Multi-tenant organization management")]
#[OA\Tag(name: "Branches", description: "Organization branch management")]
#[OA\Tag(name: "Parties", description: "Customer, Supplier, and other party management")]
#[OA\Tag(name: "Invoices", description: "Sales and purchase invoice management")]
#[OA\Tag(name: "Products", description: "Product catalog and inventory management")]
#[OA\Tag(name: "Accounts", description: "Chart of accounts management")]
#[OA\Tag(name: "Payments", description: "Payment tracking and allocation")]
#[OA\Tag(name: "Warehouses", description: "Warehouse and stock management")]
#[OA\Tag(name: "Employees", description: "Human resources and employee management")]
#[OA\Tag(name: "Projects", description: "Project management and tracking")]
#[OA\Tag(name: "Tasks", description: "Task management and assignment")]
#[OA\Tag(name: "Currencies", description: "Currency management and exchange rates")]
#[OA\Tag(name: "Account Services", description: "Account service management")]
#[OA\Tag(name: "Plans", description: "Public plan browsing")]
#[OA\Tag(name: "Subscriptions", description: "User subscription endpoints")]
#[OA\Tag(name: "Admin/Plans", description: "Admin plan management endpoints")]
#[OA\Tag(name: "Admin/Users", description: "Admin user management endpoints")]
#[OA\Tag(name: "Admin/Subscriptions", description: "Admin subscription management endpoints")]
#[OA\Schema(
    schema: "ApiResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Success"),
        new OA\Property(property: "data", type: "object", nullable: true),
    ]
)]
#[OA\Schema(
    schema: "MessageResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", type: "object", nullable: true),
    ]
)]
class BaseSchemas
{
    // Base API and shared response schemas.
}
