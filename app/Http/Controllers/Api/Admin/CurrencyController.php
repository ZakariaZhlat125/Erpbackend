<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Currency\StoreCurrencyRequest;
use App\Http\Requests\Currency\UpdateCurrencyRequest;
use App\Http\Resources\CurrencyResource;
use App\Services\CurrencyService;
use Illuminate\Http\JsonResponse;

class CurrencyController extends BaseApiController
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->currencyService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreCurrencyRequest $request): JsonResponse
    {
        $currency = $this->currencyService->create($request->validated());

        return $this->createdResponse(
            new CurrencyResource($currency)
        );
    }

    public function show(int $id): JsonResponse
    {
        $currency = $this->currencyService->findById($id);

        if (!$currency) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new CurrencyResource($currency)
        );
    }

    public function update(UpdateCurrencyRequest $request, int $id): JsonResponse
    {
        if (!$this->currencyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->currencyService->update($id, $request->validated());
        $currency = $this->currencyService->findById($id);

        return $this->successResponse(
            new CurrencyResource($currency),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->currencyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->currencyService->delete($id);

        return $this->noContentResponse();
    }

    public function setBase(int $id): JsonResponse
    {
        if (!$this->currencyService->exists($id)) {
            return $this->notFoundResponse();
        }

        try {
            $this->currencyService->setAsBase($id);

            return $this->successResponse(
                null,
                'Base currency updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateRate(int $id): JsonResponse
    {
        if (!$this->currencyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $validated = request()->validate([
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $this->currencyService->updateExchangeRate($id, $validated['exchange_rate']);

        return $this->successResponse(
            null,
            'Exchange rate updated successfully'
        );
    }

    public function toggleActive(int $id): JsonResponse
    {
        if (!$this->currencyService->exists($id)) {
            return $this->notFoundResponse();
        }

        try {
            $this->currencyService->toggleActive($id);

            return $this->successResponse(
                null,
                'Currency status toggled successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
