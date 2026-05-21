<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Task",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "organization_id", type: "integer", example: 1),
        new OA\Property(property: "project_id", type: "integer", example: 1),
        new OA\Property(property: "parent_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "title", type: "string", example: "Design homepage mockup"),
        new OA\Property(property: "description", type: "string", example: "Create wireframes and visual design for the homepage", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["todo", "in_progress", "done", "blocked"], example: "todo"),
        new OA\Property(property: "priority", type: "string", enum: ["low", "medium", "high", "urgent"], example: "medium"),
        new OA\Property(property: "assigned_user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "due_date", type: "string", format: "date", example: "2024-03-15", nullable: true),
        new OA\Property(property: "estimated_minutes", type: "integer", example: 480, nullable: true),
        new OA\Property(property: "actual_minutes", type: "integer", example: 360, nullable: true),
        new OA\Property(property: "created_by", type: "integer", example: 1),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "TaskStoreRequest",
    required: ["project_id", "title"],
    properties: [
        new OA\Property(property: "project_id", type: "integer", example: 1),
        new OA\Property(property: "parent_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "title", type: "string", example: "Design homepage mockup"),
        new OA\Property(property: "description", type: "string", example: "Create wireframes and visual design for the homepage", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["todo", "in_progress", "done", "blocked"], example: "todo", nullable: true),
        new OA\Property(property: "priority", type: "string", enum: ["low", "medium", "high", "urgent"], example: "medium", nullable: true),
        new OA\Property(property: "assigned_user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "due_date", type: "string", format: "date", example: "2024-03-15", nullable: true),
        new OA\Property(property: "estimated_minutes", type: "integer", example: 480, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "TaskUpdateRequest",
    properties: [
        new OA\Property(property: "project_id", type: "integer", example: 1),
        new OA\Property(property: "parent_id", type: "integer", example: null, nullable: true),
        new OA\Property(property: "title", type: "string", example: "Design homepage mockup - Updated"),
        new OA\Property(property: "description", type: "string", example: "Updated task description", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["todo", "in_progress", "done", "blocked"], example: "in_progress"),
        new OA\Property(property: "priority", type: "string", enum: ["low", "medium", "high", "urgent"], example: "high"),
        new OA\Property(property: "assigned_user_id", type: "integer", example: 5, nullable: true),
        new OA\Property(property: "due_date", type: "string", format: "date", example: "2024-03-15", nullable: true),
        new OA\Property(property: "estimated_minutes", type: "integer", example: 480, nullable: true),
        new OA\Property(property: "actual_minutes", type: "integer", example: 360, nullable: true),
    ]
)]
#[OA\Schema(
    schema: "TaskUpdateStatusRequest",
    required: ["status"],
    properties: [
        new OA\Property(property: "status", type: "string", enum: ["todo", "in_progress", "done", "blocked"], example: "done"),
    ]
)]
#[OA\Schema(
    schema: "TaskResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/Task"),
    ]
)]
#[OA\Schema(
    schema: "TaskListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/Task")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]
#[OA\Get(
    path: "/projects/{projectId}/tasks",
    summary: "List tasks for a project",
    description: "Returns paginated list of tasks within a project",
    security: [["sanctum" => []]],
    tags: ["Tasks"],
    parameters: [
        new OA\Parameter(name: "projectId", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "per_page", in: "query", description: "Items per page", required: false, schema: new OA\Schema(type: "integer", example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/TaskListResponse")),
        new OA\Response(response: 401, description: "Unauthenticated"),
        new OA\Response(response: 403, description: "Forbidden"),
    ]
)]
#[OA\Post(
    path: "/projects/{projectId}/tasks",
    summary: "Create new task",
    description: "Create a new task within a project",
    security: [["sanctum" => []]],
    tags: ["Tasks"],
    parameters: [
        new OA\Parameter(name: "projectId", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/TaskStoreRequest")),
    responses: [
        new OA\Response(response: 201, description: "Task created successfully", content: new OA\JsonContent(ref: "#/components/schemas/TaskResponse")),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Get(
    path: "/projects/{projectId}/tasks/{id}",
    summary: "Get task by ID",
    security: [["sanctum" => []]],
    tags: ["Tasks"],
    parameters: [
        new OA\Parameter(name: "projectId", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "id", in: "path", description: "Task ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Successful operation", content: new OA\JsonContent(ref: "#/components/schemas/TaskResponse")),
        new OA\Response(response: 404, description: "Task not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/projects/{projectId}/tasks/{id}",
    summary: "Update task",
    security: [["sanctum" => []]],
    tags: ["Tasks"],
    parameters: [
        new OA\Parameter(name: "projectId", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "id", in: "path", description: "Task ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/TaskUpdateRequest")),
    responses: [
        new OA\Response(response: 200, description: "Task updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/TaskResponse")),
        new OA\Response(response: 404, description: "Task not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Delete(
    path: "/projects/{projectId}/tasks/{id}",
    summary: "Delete task",
    security: [["sanctum" => []]],
    tags: ["Tasks"],
    parameters: [
        new OA\Parameter(name: "projectId", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "id", in: "path", description: "Task ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "Task deleted successfully"),
        new OA\Response(response: 404, description: "Task not found"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Put(
    path: "/projects/{projectId}/tasks/{id}/status",
    summary: "Update task status",
    description: "Update only the status of a task",
    security: [["sanctum" => []]],
    tags: ["Tasks"],
    parameters: [
        new OA\Parameter(name: "projectId", in: "path", description: "Project ID", required: true, schema: new OA\Schema(type: "integer")),
        new OA\Parameter(name: "id", in: "path", description: "Task ID", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/TaskUpdateStatusRequest")),
    responses: [
        new OA\Response(response: 200, description: "Task status updated successfully", content: new OA\JsonContent(ref: "#/components/schemas/TaskResponse")),
        new OA\Response(response: 404, description: "Task not found"),
        new OA\Response(response: 422, description: "Validation error"),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
class TaskSchemas
{
    // Task schemas and endpoint documentation.
}
