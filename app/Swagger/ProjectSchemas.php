<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Project",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "code", type: "string", example: "PRJ-001"),
        new OA\Property(property: "name", type: "string", example: "Website Redesign"),
        new OA\Property(property: "description", type: "string", example: "Complete website redesign project", nullable: true),
        new OA\Property(property: "party_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["planning", "active", "on_hold", "completed", "cancelled"], example: "active"),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-06-30", nullable: true),
        new OA\Property(property: "budget_amount", type: "number", format: "float", example: 50000.00, nullable: true),
        new OA\Property(property: "progress_percent", type: "integer", example: 45),
        new OA\Property(property: "created_by", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "ProjectStoreRequest",
    required: ["code", "name", "start_date"],
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "code", type: "string", example: "PRJ-001"),
        new OA\Property(property: "name", type: "string", example: "Website Redesign"),
        new OA\Property(property: "description", type: "string", example: "Complete website redesign project", nullable: true),
        new OA\Property(property: "party_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["planning", "active", "on_hold", "completed", "cancelled"], example: "planning", nullable: true),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-06-30", nullable: true),
        new OA\Property(property: "budget_amount", type: "number", format: "float", example: 50000.00, nullable: true),
        new OA\Property(property: "progress_percent", type: "integer", example: 0, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "ProjectUpdateRequest",
    properties: [
        new OA\Property(property: "branch_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "code", type: "string", example: "PRJ-001"),
        new OA\Property(property: "name", type: "string", example: "Website Redesign Updated"),
        new OA\Property(property: "description", type: "string", example: "Updated project description", nullable: true),
        new OA\Property(property: "party_id", type: "integer", example: 1, nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["planning", "active", "on_hold", "completed", "cancelled"], example: "active"),
        new OA\Property(property: "start_date", type: "string", format: "date", example: "2024-01-01"),
        new OA\Property(property: "end_date", type: "string", format: "date", example: "2024-06-30", nullable: true),
        new OA\Property(property: "budget_amount", type: "number", format: "float", example: 55000.00, nullable: true),
        new OA\Property(property: "progress_percent", type: "integer", example: 50, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "ProjectResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Project"),
    ]
)]
#[OA\Schema(
    schema: "ProjectListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Project")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Schema(
    schema: "ProjectUpdateProgressRequest",
    required: ["progress_percent"],
    properties: [
        new OA\Property(property: "progress_percent", type: "integer", example: 75, minimum: 0, maximum: 100),
    ]
)]
#[OA\Get(
    path: "/projects",
    summary: "List projects",
    description: "Returns paginated list of projects",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/ProjectListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/projects",
    summary: "Create new project",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProjectStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Project created successfully", content: new OA\JsonContent(ref: "#/components/schemas/ProjectResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/projects/statistics",
    summary: "Get project statistics",
    description: "Retrieve aggregated project statistics",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    responses: [
        new OA\Response(response: 200, description: "Statistics retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/projects/search",
    summary: "Search projects",
    description: "Search projects by various criteria",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "code_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "name_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "description_like", in: "query", required: false, schema: new OA\Schema(type: "string")),
        new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["planning", "active", "on_hold", "completed", "cancelled"])),
        new OA\Parameter(name: "party_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "start_date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "start_date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "end_date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "end_date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date")),
        new OA\Parameter(name: "budget_amount_from", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "budget_amount_to", in: "query", required: false, schema: new OA\Schema(type: "number")),
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Search results", content: new OA\JsonContent(ref: "#/components/schemas/ProjectListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/projects/export",
    summary: "Export projects",
    description: "Export projects data to Excel",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    responses: [
        new OA\Response(response: 200, description: "Export file download"),
        new OA\Response(response: 501, description: "Not implemented"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/projects/{id}",
    summary: "Get project by ID",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/ProjectResponse")),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/projects/{id}",
    summary: "Update project",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProjectUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Project updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/ProjectResponse")),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/projects/{id}",
    summary: "Delete project",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Project deleted successfully"),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/projects/{id}/dashboard",
    summary: "Get project dashboard",
    description: "Retrieve project dashboard with summary data",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Dashboard retrieved", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/projects/{id}/progress",
    summary: "Update project progress",
    description: "Update the progress percentage of a project",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/ProjectUpdateProgressRequest")),
    responses: [
        new OA\Response(response: 200, description: "Progress updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/ProjectResponse")),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 400, description: "Bad request"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/projects/{id}/members",
    summary: "Add project member",
    description: "Add a member to the project",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["user_id"],
            properties: [
                new OA\Property(property: "user_id", type: "integer", example: 5),
                new OA\Property(property: "role", type: "string", example: "developer", nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: "Member added successfully", content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/projects/{id}/members/{userId}",
    summary: "Remove project member",
    description: "Remove a member from the project",
    security: [["sanctum" => []]],
    tags: ["Projects"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "userId", in: "path", description: "User ID to remove", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Member removed successfully"),
        new OA\Response(response: 404, description: "Project not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class ProjectSchemas
{
    // Project schemas and endpoint documentation.
}
