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

class AuthController extends BaseApiController
{
    public function __construct(
        protected AuthService $authService
    ) {}

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

    public function me(Request $request): JsonResponse
    {
        $user = $this->authService->me($request->user());

        return $this->successResponse(new UserResource($user), 'User details retrieved');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(null, 'Logged out successfully');
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->validate(['refreshToken' => 'required|string']);

        $payload = $this->authService->refresh($request->refreshToken);

        return $this->successResponse([
            'accessToken' => $payload['accessToken'],
            'refreshToken' => $payload['refreshToken'],
            'expiresIn' => $payload['expiresIn'],
        ], 'Token refreshed');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->successResponse(null, 'Password reset email sent');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        return $this->successResponse(null, 'Password reset successful');
    }
}
