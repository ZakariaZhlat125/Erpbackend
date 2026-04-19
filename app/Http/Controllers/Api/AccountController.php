<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;

class AccountController extends BaseApiController
{
    public function __construct(
        protected AccountService $accountService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->accountService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->accountService->create($request->validated());

        return $this->createdResponse(
            new AccountResource($account)
        );
    }

    public function show(int $id): JsonResponse
    {
        $account = $this->accountService->findById($id);

        if (!$account) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new AccountResource($account)
        );
    }

    public function update(UpdateAccountRequest $request, int $id): JsonResponse
    {
        if (!$this->accountService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->accountService->update($id, $request->validated());
        $account = $this->accountService->findById($id);

        return $this->successResponse(
            new AccountResource($account),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->accountService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->accountService->delete($id);

        return $this->noContentResponse();
    }
}
