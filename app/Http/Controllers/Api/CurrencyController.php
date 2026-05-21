<?php

namespace App\Http\Controllers\Api;

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
        $currencies = $this->currencyService->getActive();

        return $this->successResponse(
            CurrencyResource::collection($currencies)
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
}
