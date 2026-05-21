<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AuthTokenPayload",
    properties: [
        new OA\Property(property: "user", ref: "#/components/schemas/User"),
        new OA\Property(property: "accessToken", type: "string"),
        new OA\Property(property: "refreshToken", type: "string"),
        new OA\Property(property: "expiresIn", type: "integer", example: 900),
    ]
)]
#[OA\Schema(
    schema: "AuthResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Login successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/AuthTokenPayload"),
    ]
)]
#[OA\Schema(
    schema: "AuthCompanyPayload",
    properties: [
        new OA\Property(property: "user", ref: "#/components/schemas/User"),
        new OA\Property(property: "organization", type: "object"),
        new OA\Property(property: "accessToken", type: "string"),
        new OA\Property(property: "refreshToken", type: "string"),
        new OA\Property(property: "expiresIn", type: "integer", example: 900),
    ]
)]
#[OA\Schema(
    schema: "AuthCompanyResponse",
    properties: [
        new OA\Property(property: "success", type: "boolean", example: true),
        new OA\Property(property: "message", type: "string", example: "Company registration successful"),
        new OA\Property(property: "data", ref: "#/components/schemas/AuthCompanyPayload"),
    ]
)]
// --- Auth endpoints ---

#[OA\Post(
    path: "/auth/login",
    operationId: "apiLogin",
    summary: "User login (API)",
    description: "Authenticate user and return access token",
    tags: ["Authentication"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "password"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "admin@example.com"),
                new OA\Property(property: "password", type: "string", format: "password", example: "password"),
                new OA\Property(property: "rememberMe", type: "boolean", example: false),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Login successful",
            content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
        ),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Post(
    path: "/auth/register-company",
    operationId: "apiRegisterCompany",
    summary: "Register new company with organization (API)",
    description: "Create a new organization and the first admin user account",
    tags: ["Authentication"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["company_name", "legal_name", "tax_number", "name", "email", "password"],
            properties: [
                new OA\Property(property: "company_name", type: "string", example: "ABC Company"),
                new OA\Property(property: "legal_name", type: "string", example: "ABC Company LLC"),
                new OA\Property(property: "tax_number", type: "string", example: "123456789"),
                new OA\Property(property: "name", type: "string", example: "John Doe"),
                new OA\Property(property: "email", type: "string", format: "email", example: "john@abccompany.com"),
                new OA\Property(property: "password", type: "string", format: "password", minLength: 8),
                new OA\Property(property: "phone", type: "string", example: "+1234567890"),
                new OA\Property(property: "address", type: "string", example: "123 Business Street"),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: "Company registration successful",
            content: new OA\JsonContent(ref: "#/components/schemas/AuthCompanyResponse")
        ),
        new OA\Response(response: 422, description: "Validation error"),
    ]
)]
#[OA\Get(
    path: "/auth/me",
    operationId: "getCurrentUser",
    summary: "Get current user",
    description: "Get authenticated user details with roles and permissions",
    security: [["sanctum" => []]],
    tags: ["Authentication"],
    responses: [
        new OA\Response(
            response: 200,
            description: "User details retrieved",
            content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
        ),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/auth/logout",
    operationId: "apiLogout",
    summary: "User logout (API)",
    description: "Revoke current access token",
    security: [["sanctum" => []]],
    tags: ["Authentication"],
    responses: [
        new OA\Response(
            response: 200,
            description: "Logout successful",
            content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")
        ),
        new OA\Response(response: 401, description: "Unauthenticated"),
    ]
)]
#[OA\Post(
    path: "/auth/refresh",
    operationId: "refreshToken",
    summary: "Refresh access token",
    description: "Generate a new access token using a refresh token",
    tags: ["Authentication"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["refreshToken"],
            properties: [
                new OA\Property(property: "refreshToken", type: "string", example: "your-refresh-token"),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Token refreshed",
            content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
        ),
        new OA\Response(response: 401, description: "Invalid or expired refresh token"),
    ]
)]
#[OA\Post(
    path: "/auth/forgot-password",
    operationId: "forgotPassword",
    summary: "Request password reset",
    description: "Send password reset link to email",
    tags: ["Authentication"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Reset link sent",
            content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")
        ),
    ]
)]
#[OA\Post(
    path: "/auth/reset-password",
    operationId: "resetPassword",
    summary: "Reset password",
    description: "Reset user password using token",
    tags: ["Authentication"],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["email", "token", "password", "password_confirmation"],
            properties: [
                new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                new OA\Property(property: "token", type: "string", example: "reset-token"),
                new OA\Property(property: "password", type: "string", format: "password"),
                new OA\Property(property: "password_confirmation", type: "string", format: "password"),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: "Password reset successful",
            content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")
        ),
    ]
)]
class AuthSchemas
{
    // Auth-related schemas and endpoint documentation.
}
