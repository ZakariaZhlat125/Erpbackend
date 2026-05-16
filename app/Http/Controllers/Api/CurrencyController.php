<?php

namespace App\Http\Controllers\Api;

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
        if (request()->has('active_only')) {
            $currencies = $this->currencyService->getActive();
            return $this->successResponse(CurrencyResource::collection($currencies));
        }

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

    public function getBase(): JsonResponse
    {
        $currency = $this->currencyService->getBaseCurrency();

        if (!$currency) {
            return $this->notFoundResponse('No base currency set');
        }

        return $this->successResponse(
            new CurrencyResource($currency)
        );
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

    public function convert(): JsonResponse
    {
        $validated = request()->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $convertedAmount = $this->currencyService->convert(
            $validated['from_currency_id'],
            $validated['to_currency_id'],
            $validated['amount']
        );

        return $this->successResponse([
            'original_amount' => $validated['amount'],
            'converted_amount' => $convertedAmount,
            'from_currency_id' => $validated['from_currency_id'],
            'to_currency_id' => $validated['to_currency_id'],
        ]);
    }

    public function active(): JsonResponse
    {
        $currencies = $this->currencyService->getActive();

        return $this->successResponse(
            CurrencyResource::collection($currencies)
        );
    }
}
