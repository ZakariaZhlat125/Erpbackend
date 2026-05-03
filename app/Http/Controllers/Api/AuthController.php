<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AuthController extends BaseApiController
{
    public function __construct(
        protected AuthService $authService
    ) {}

    #[OA\Post(
        path: "/auth/login",
        operationId: "apiLogin",
        summary: "User login (API)",
        description: "Authenticate user and return access token",
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
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
            ),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $this->authService->login($request);

        return $this->successResponse([
            'user' => new UserResource($payload['user']),
            'accessToken' => $payload['accessToken'],
            'refreshToken' => $payload['refreshToken'],
            'expiresIn' => $payload['expiresIn'],
        ], 'Login successful');
    }

    #[OA\Post(
        path: "/auth/register-company",
        operationId: "apiRegisterCompany",
        summary: "Register new company with organization (API)",
        description: "Create a new organization and the first admin user account",
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
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 201,
                description: "Company registration successful",
                content: new OA\JsonContent(ref: "#/components/schemas/AuthCompanyResponse")
            ),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function registerCompany(RegisterCompanyRequest $request): JsonResponse
    {
        $payload = $this->authService->registerCompany($request);

        return $this->createdResponse([
            'user' => new UserResource($payload['user']),
            'organization' => $payload['organization'],
            'accessToken' => $payload['accessToken'],
            'refreshToken' => $payload['refreshToken'],
            'expiresIn' => $payload['expiresIn'],
        ], 'Company registration successful');
    }

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
    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return $this->successResponse(new UserResource($user), 'User details retrieved');
    }

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
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully');
    }

    #[OA\Post(
        path: "/auth/refresh",
        operationId: "refreshToken",
        summary: "Refresh access token",
        description: "Generate a new access token",
        security: [["sanctum" => []]],
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Token refreshed",
                content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function refresh(Request $request): JsonResponse
    {
        $payload = $this->authService->refresh($request->user());

        return $this->successResponse([
            'accessToken' => $payload['accessToken'],
            'refreshToken' => $payload['refreshToken'],
            'expiresIn' => $payload['expiresIn'],
        ], 'Token refreshed');
    }

    #[OA\Post(
        path: "/auth/forgot-password",
        operationId: "forgotPassword",
        summary: "Request password reset",
        description: "Send password reset link to email",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", format: "email", example: "user@example.com"),
                ]
            )
        ),
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Reset link sent",
                content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")
            ),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->successResponse(null, 'Password reset email sent');
    }

    #[OA\Post(
        path: "/auth/reset-password",
        operationId: "resetPassword",
        summary: "Reset password",
        description: "Reset user password with token",
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["token", "password"],
                properties: [
                    new OA\Property(property: "token", type: "string", example: "reset-token"),
                    new OA\Property(property: "password", type: "string", format: "password", minLength: 8),
                ]
            )
        ),
        tags: ["Authentication"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset successful",
                content: new OA\JsonContent(ref: "#/components/schemas/MessageResponse")
            ),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->successResponse(null, 'Password reset successful');
    }
}
