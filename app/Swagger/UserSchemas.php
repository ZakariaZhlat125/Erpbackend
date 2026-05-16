<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin/Users", description: "Admin user management endpoints")]
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
class UserSchemas
{
}
