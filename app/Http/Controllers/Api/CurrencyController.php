<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CurrencyController extends Controller
{
    public function __construct(
        protected CurrencyService $currencyService
    ) {}

    /**
     * Display a listing of currencies
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        
        if ($request->has('active_only')) {
            $currencies = $this->currencyService->getActive();
            return response()->json(['data' => $currencies]);
        }

        $currencies = $this->currencyService->paginate($perPage);
        return response()->json($currencies);
    }

    /**
     * Store a newly created currency
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|size:3|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'decimal_separator' => 'nullable|string|size:1',
            'thousands_separator' => 'nullable|string|size:1',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'exchange_rate' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $currency = $this->currencyService->create($validated);

        return response()->json([
            'message' => 'Currency created successfully',
            'data' => $currency
        ], 201);
    }

    /**
     * Display the specified currency
     */
    public function show(int $id): JsonResponse
    {
        $currency = $this->currencyService->find($id);
        return response()->json(['data' => $currency]);
    }

    /**
     * Update the specified currency
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'sometimes|string|size:3|unique:currencies,code,' . $id,
            'name' => 'sometimes|string|max:255',
            'symbol' => 'sometimes|string|max:10',
            'decimal_separator' => 'nullable|string|size:1',
            'thousands_separator' => 'nullable|string|size:1',
            'decimal_places' => 'nullable|integer|min:0|max:4',
            'exchange_rate' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $currency = $this->currencyService->update($id, $validated);

        return response()->json([
            'message' => 'Currency updated successfully',
            'data' => $currency
        ]);
    }

    /**
     * Remove the specified currency
     */
    public function destroy(int $id): JsonResponse
    {
        $this->currencyService->delete($id);

        return response()->json([
            'message' => 'Currency deleted successfully'
        ]);
    }

    /**
     * Get base currency
     */
    public function getBase(): JsonResponse
    {
        $currency = $this->currencyService->getBaseCurrency();
        
        if (!$currency) {
            return response()->json([
                'message' => 'No base currency set'
            ], 404);
        }

        return response()->json(['data' => $currency]);
    }

    /**
     * Set currency as base
     */
    public function setBase(int $id): JsonResponse
    {
        try {
            $this->currencyService->setAsBase($id);
            
            return response()->json([
                'message' => 'Base currency updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Update exchange rate
     */
    public function updateRate(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'exchange_rate' => 'required|numeric|min:0',
        ]);

        $this->currencyService->updateExchangeRate($id, $validated['exchange_rate']);

        return response()->json([
            'message' => 'Exchange rate updated successfully'
        ]);
    }

    /**
     * Toggle currency active status
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $this->currencyService->toggleActive($id);
            
            return response()->json([
                'message' => 'Currency status toggled successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Convert amount between currencies
     */
    public function convert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_currency_id' => 'required|exists:currencies,id',
            'to_currency_id' => 'required|exists:currencies,id',
            'amount' => 'required|numeric|min:0',
        ]);

        $convertedAmount = $this->currencyService->convert(
            $validated['from_currency_id'],
            $validated['to_currency_id'],
            $validated['amount']
        );

        return response()->json([
            'data' => [
                'original_amount' => $validated['amount'],
                'converted_amount' => $convertedAmount,
                'from_currency_id' => $validated['from_currency_id'],
                'to_currency_id' => $validated['to_currency_id'],
            ]
        ]);
    }

    /**
     * Get active currencies only
     */
    public function active(): JsonResponse
    {
        $currencies = $this->currencyService->getActive();
        return response()->json(['data' => $currencies]);
    }
}
