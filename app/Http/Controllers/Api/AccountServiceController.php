<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\AccountService\StoreAccountServiceRequest;
use App\Http\Requests\AccountService\UpdateAccountServiceRequest;
use App\Http\Resources\AccountServiceResource;
use App\Services\AccountServiceService;
use Illuminate\Http\JsonResponse;

class AccountServiceController extends BaseApiController
{
    public function __construct(
        protected AccountServiceService $accountServiceService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->accountServiceService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreAccountServiceRequest $request): JsonResponse
    {
        $accountService = $this->accountServiceService->create($request->validated());

        return $this->createdResponse(
            new AccountServiceResource($accountService)
        );
    }

    public function show(int $id): JsonResponse
    {
        $accountService = $this->accountServiceService->findById($id);

        if (!$accountService) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new AccountServiceResource($accountService)
        );
    }

    public function update(UpdateAccountServiceRequest $request, int $id): JsonResponse
    {
        if (!$this->accountServiceService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->accountServiceService->update($id, $request->validated());
        $accountService = $this->accountServiceService->findById($id);

        return $this->successResponse(
            new AccountServiceResource($accountService),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->accountServiceService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->accountServiceService->delete($id);

        return $this->noContentResponse();
    }
}
