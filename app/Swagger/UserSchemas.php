<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    required: ["id", "name", "email"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "+1234567890"),
        new OA\Property(property: "organization_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "branch_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "avatar_path", type: "string", nullable: true, example: "/avatars/user.jpg"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["admin", "manager"]),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "StoreUserRequest",
    required: ["name", "email", "password"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
        new OA\Property(property: "password", type: "string", format: "password", example: "password123"),
        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password123"),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "+1234567890"),
        new OA\Property(property: "organization_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "branch_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "avatar_path", type: "string", nullable: true, example: "/avatars/user.jpg"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["admin"]),
    ]
)]
#[OA\Schema(
    schema: "UpdateUserRequest",
    properties: [
        new OA\Property(property: "name", type: "string", example: "John Doe"),
        new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
        new OA\Property(property: "password", type: "string", format: "password", nullable: true, example: "newpassword123"),
        new OA\Property(property: "password_confirmation", type: "string", format: "password", nullable: true, example: "newpassword123"),
        new OA\Property(property: "phone", type: "string", nullable: true, example: "+1234567890"),
        new OA\Property(property: "organization_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "branch_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "avatar_path", type: "string", nullable: true, example: "/avatars/user.jpg"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["manager"]),
    ]
)]
#[OA\Schema(
    schema: "UserResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Operation successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/User"),
    ]
)]
#[OA\Schema(
    schema: "UserListResponse",
    properties: [
        new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/User")),
        new OA\Property(property: "meta", type: "object"),
    ]
)]

// --- Admin User endpoints ---

#[OA\Get(
    path: "/admin/users",
    summary: "Get all users",
    security: [["sanctum" => []]],
    tags: ["Admin/Users"],
    parameters: [
        new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/UserListResponse")),
        new OA\Response(response: 401, description: "Unauthorized"),
    ]
)]
#[OA\Post(
    path: "/admin/users",
    summary: "Create a new user",
    security: [["sanctum" => []]],
    tags: ["Admin/Users"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/StoreUserRequest")
    ),
    responses: [
        new OA\Response(response: 201, description: "User created", content: new OA\JsonContent(ref: "#/components/schemas/UserResponse")),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Get(
    path: "/admin/users/{id}",
    summary: "Get user by ID",
    security: [["sanctum" => []]],
    tags: ["Admin/Users"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 200, description: "Success", content: new OA\JsonContent(ref: "#/components/schemas/UserResponse")),
        new OA\Response(response: 404, description: "User not found"),
    ]
)]
#[OA\Put(
    path: "/admin/users/{id}",
    summary: "Update user",
    security: [["sanctum" => []]],
    tags: ["Admin/Users"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(ref: "#/components/schemas/UpdateUserRequest")
    ),
    responses: [
        new OA\Response(response: 200, description: "User updated", content: new OA\JsonContent(ref: "#/components/schemas/UserResponse")),
        new OA\Response(response: 404, description: "User not found"),
    ]
)]
#[OA\Delete(
    path: "/admin/users/{id}",
    summary: "Delete user",
    security: [["sanctum" => []]],
    tags: ["Admin/Users"],
    parameters: [
        new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
    ],
    responses: [
        new OA\Response(response: 204, description: "User deleted"),
        new OA\Response(response: 404, description: "User not found"),
    ]
)]
class UserSchemas
{
    // User schemas and endpoint documentation.
}
