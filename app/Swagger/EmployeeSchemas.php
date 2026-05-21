<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Employee",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "employee_number", type: "string", example: "EMP-001"),
        new OA\Property(property: "first_name", type: "string", example: "John"),
        new OA\Property(property: "last_name", type: "string", example: "Doe"),
        new OA\Property(property: "full_name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", nullable: true),
        new OA\Property(property: "phone", type: "string", example: "+1234567890", nullable: true),
        new OA\Property(property: "hire_date", type: "string", format: "date", example: "2024-01-15"),
        new OA\Property(property: "job_title", type: "string", example: "Software Engineer", nullable: true),
        new OA\Property(property: "department_name", type: "string", example: "Engineering", nullable: true),
        new OA\Property(property: "manager_employee_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "terminated"], example: "active"),
        new OA\Property(property: "base_salary", type: "number", format: "float", example: 5000.00, nullable: true),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "EmployeeStoreRequest",
    required: ["employee_number", "first_name", "last_name", "hire_date"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "employee_number", type: "string", example: "EMP-001"),
        new OA\Property(property: "first_name", type: "string", example: "John"),
        new OA\Property(property: "last_name", type: "string", example: "Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", nullable: true),
        new OA\Property(property: "phone", type: "string", example: "+1234567890", nullable: true),
        new OA\Property(property: "hire_date", type: "string", format: "date", example: "2024-01-15"),
        new OA\Property(property: "job_title", type: "string", example: "Software Engineer", nullable: true),
        new OA\Property(property: "department_name", type: "string", example: "Engineering", nullable: true),
        new OA\Property(property: "manager_employee_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "terminated"], example: "active", nullable: true),
        new OA\Property(property: "base_salary", type: "number", format: "float", example: 5000.00, nullable: true),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "EmployeeUpdateRequest",
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "employee_number", type: "string", example: "EMP-001"),
        new OA\Property(property: "first_name", type: "string", example: "John"),
        new OA\Property(property: "last_name", type: "string", example: "Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john.doe@example.com", nullable: true),
        new OA\Property(property: "phone", type: "string", example: "+1234567890", nullable: true),
        new OA\Property(property: "hire_date", type: "string", format: "date", example: "2024-01-15"),
        new OA\Property(property: "job_title", type: "string", example: "Software Engineer", nullable: true),
        new OA\Property(property: "department_name", type: "string", example: "Engineering", nullable: true),
        new OA\Property(property: "manager_employee_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "terminated"], example: "active", nullable: true),
        new OA\Property(property: "base_salary", type: "number", format: "float", example: 5000.00, nullable: true),
        new OA\Property(property: "currency_id", type: "integer", example: 1, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "EmployeeResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Employee"),
    ]
)]
#[OA\Schema(
    schema: "EmployeeListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Employee")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "EmployeeBulkUpdateStatusRequest",
    required: ["employee_ids", "status"],
    properties: [
        new OA\Property(property: "employee_ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2, 3]),
        new OA\Property(property: "status", type: "string", enum: ["active", "inactive", "terminated"], example: "active"),
    ]
)]
#[OA\Get(
    path: "/hr/employees",
    summary: "List employees",
    description: "Returns paginated list of employees",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/EmployeeListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/hr/employees",
    summary: "Create new employee",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/EmployeeStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Employee created successfully", content: new OA\JsonContent(ref: "#/components/schemas/EmployeeResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/hr/employees/statistics",
    summary: "Get employee statistics",
    description: "Retrieve aggregated employee statistics",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    responses: [
        new OA\Response(response: 200, description: "Statistics retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/hr/employees/search",
    summary: "Search employees",
    description: "Search employees by various criteria",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    parameters: [
        new OA\Parameter(name: "employee_number_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "full_name_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "first_name_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "last_name_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "email", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "department_name", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "job_title_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["active", "inactive", "terminated"])),
        new OA\Parameter(name: "hire_date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "hire_date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "base_salary_from", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "base_salary_to", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Search results", content: new OA\JsonContent(ref: "#/components/schemas/EmployeeListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/hr/employees/org-chart",
    summary: "Get organization chart",
    description: "Retrieve the organizational chart of employees",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    responses: [
        new OA\Response(response: 200, description: "Organization chart retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/hr/employees/export",
    summary: "Export employees",
    description: "Export employees data to Excel",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    responses: [
        new OA\Response(response: 200, description: "Export file download"),
        new OA\Response(response: 501, description: "Not implemented"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/hr/employees/bulk-update-status",
    summary: "Bulk update employee status",
    description: "Update status for multiple employees at once",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/EmployeeBulkUpdateStatusRequest")),
    responses: [
        new OA\Response(response: 200, description: "Bulk status update successful", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/hr/employees/import",
    summary: "Import employees",
    description: "Import employees from an Excel file",
    security: [["sanctum" => []]],
    tags: ["Employees"],
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
    path: "/hr/employees/{id}",
    summary: "Get employee by ID",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Employee ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/EmployeeResponse")),
        new OA\Response(response: 404, description: "Employee not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/hr/employees/{id}",
    summary: "Update employee",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Employee ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/EmployeeUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Employee updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/EmployeeResponse")),
        new OA\Response(response: 404, description: "Employee not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/hr/employees/{id}",
    summary: "Delete employee",
    security: [["sanctum" => []]],
    tags: ["Employees"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Employee ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Employee deleted successfully"),
        new OA\Response(response: 404, description: "Employee not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class EmployeeSchemas
{
    // Employee schemas and endpoint documentation.
}
