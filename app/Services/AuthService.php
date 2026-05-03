<?php

namespace App\Services;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterCompanyRequest;
use App\Repositories\Contracts\OrganizationRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthService extends BaseService
{
    public function __construct(
        UserRepositoryInterface $userRepository,
        protected OrganizationRepositoryInterface $organizationRepository
    ) {
        parent::__construct($userRepository);
    }

    public function login(LoginRequest $request): array
    {
        $user = $this->repository->findByField('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'accessToken' => $token,
            'refreshToken' => $token,
            'expiresIn' => 900,
        ];
    }

    public function registerCompany(RegisterCompanyRequest $request): array
    {
        [$user, $organization] = DB::transaction(function () use ($request) {
            $user = $this->repository->create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'is_active' => true,
            ]);

            $organization = $user->organizations()->create([
                'name' => $request->company_name,
                'legal_name' => $request->legal_name,
                'tax_number' => $request->tax_number,
                'timezone' => 'UTC',
                'locale' => 'en',
                'status' => 'active',
                'address' => $request->address,
                'phone' => $request->phone,
                'email' => $request->email,
            ]);

            $ownerRole = Role::where('name', 'OWNER_ORGANIZATION')
                ->where('guard_name', 'sanctum')
                ->first();

            if ($ownerRole) {
                $user->assignRole($ownerRole);
            }

            return [$user, $organization];
        });
        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'user' => $user,
            'organization' => $organization,
            'accessToken' => $token,
            'refreshToken' => $token,
            'expiresIn' => 900,
        ];
    }

    public function me(mixed $user): mixed
    {
        return $user;
    }

    public function logout(mixed $user): void
    {
        $user->currentAccessToken()->delete();
    }

    public function refresh(mixed $user): array
    {
        $user->currentAccessToken()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return [
            'accessToken' => $token,
            'refreshToken' => $token,
            'expiresIn' => 900,
        ];
    }
}
