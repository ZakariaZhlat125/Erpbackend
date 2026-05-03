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
class AuthSchemas
{
    // Auth-related schemas.
}
