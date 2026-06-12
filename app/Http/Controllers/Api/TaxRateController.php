<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\TaxRateResource;
use App\Models\TaxRate;
use App\Services\TaxService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaxRateController extends BaseApiController
{
    public function __construct(
        protected TaxService $taxService
    ) {}

    public function index(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        $type = request()->input('type');
        $activeOnly = request()->boolean('active_only', true);

        $query = TaxRate::where('organization_id', $organizationId)
            ->with(['salesAccount:id,code,name', 'purchaseAccount:id,code,name']);

        if ($activeOnly) {
            $query->active()->effective();
        }

        if ($type) {
            $query->byType($type);
        }

        $taxRates = $query->orderBy('name')->get();

        return $this->successResponse(TaxRateResource::collection($taxRates));
    }

    public function forSales(): JsonResponse
    {
        $taxRates = $this->taxService->getEffectiveTaxRates(
            Auth::user()->organization_id,
            'sales'
        );

        return $this->successResponse(TaxRateResource::collection($taxRates));
    }

    public function forPurchase(): JsonResponse
    {
        $taxRates = $this->taxService->getEffectiveTaxRates(
            Auth::user()->organization_id,
            'purchase'
        );

        return $this->successResponse(TaxRateResource::collection($taxRates));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rate' => 'required|numeric|min:0|max:100',
            'type' => 'required|in:sales,purchase,withholding,exempt,zero_rated',
            'calculation' => 'nullable|in:percentage,fixed',
            'sales_account_id' => 'nullable|exists:accounts,id',
            'purchase_account_id' => 'nullable|exists:accounts,id',
            'is_compound' => 'boolean',
            'is_inclusive' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after:effective_from',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;

        // If setting as default, unset other defaults
        if ($validated['is_default'] ?? false) {
            TaxRate::where('organization_id', $validated['organization_id'])
                ->where('type', $validated['type'])
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $taxRate = TaxRate::create($validated);

        return $this->createdResponse(
            new TaxRateResource($taxRate),
            'Tax rate created successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $taxRate = TaxRate::with(['salesAccount:id,code,name', 'purchaseAccount:id,code,name'])->find($id);

        if (!$taxRate) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new TaxRateResource($taxRate));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $taxRate = TaxRate::find($id);

        if (!$taxRate) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'rate' => 'sometimes|numeric|min:0|max:100',
            'sales_account_id' => 'nullable|exists:accounts,id',
            'purchase_account_id' => 'nullable|exists:accounts,id',
            'is_compound' => 'boolean',
            'is_inclusive' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date',
        ]);

        // If setting as default, unset other defaults
        if (($validated['is_default'] ?? false) && !$taxRate->is_default) {
            TaxRate::where('organization_id', $taxRate->organization_id)
                ->where('type', $taxRate->type)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $taxRate->update($validated);

        return $this->successResponse(
            new TaxRateResource($taxRate),
            'Tax rate updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $taxRate = TaxRate::find($id);

        if (!$taxRate) {
            return $this->notFoundResponse();
        }

        // Check if used
        // Add checks for invoice lines, etc.

        $taxRate->delete();

        return $this->noContentResponse();
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'tax_rate_id' => 'required|exists:tax_rates,id',
            'is_inclusive' => 'boolean',
        ]);

        $result = $this->taxService->calculateTax(
            $validated['amount'],
            $validated['tax_rate_id'],
            $validated['is_inclusive'] ?? false
        );

        return $this->successResponse($result);
    }

    public function seedDefaults(): JsonResponse
    {
        $this->taxService->seedDefaultTaxRates(Auth::user()->organization_id);

        return $this->successResponse(null, 'Default tax rates seeded successfully');
    }
}
