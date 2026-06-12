<?php

namespace App\Services;

use App\Models\TaxRate;
use App\Models\TaxTemplate;
use Illuminate\Support\Collection;

class TaxService
{
    public function calculateTax(float $amount, int $taxRateId, bool $isInclusive = false): array
    {
        $taxRate = TaxRate::findOrFail($taxRateId);
        return $taxRate->calculateTax($amount, $isInclusive);
    }

    public function calculateTaxes(float $amount, array $taxRateIds, bool $isInclusive = false): array
    {
        $results = [];
        $totalTax = 0;
        $runningAmount = $amount;

        foreach ($taxRateIds as $taxRateId) {
            $taxRate = TaxRate::find($taxRateId);
            if (!$taxRate || !$taxRate->is_active) {
                continue;
            }

            $calculation = $taxRate->calculateTax($runningAmount, $isInclusive);
            $results[] = $calculation;
            $totalTax += $calculation['tax_amount'];

            if ($taxRate->is_compound) {
                $runningAmount = $calculation['gross_amount'];
            }
        }

        return [
            'net_amount' => round($amount, 2),
            'total_tax' => round($totalTax, 2),
            'gross_amount' => round($amount + $totalTax, 2),
            'taxes' => $results,
        ];
    }

    public function calculateWithTemplate(float $amount, int $templateId, bool $isInclusive = false): array
    {
        $template = TaxTemplate::with('taxRates')->findOrFail($templateId);
        return $template->calculateTaxes($amount, $isInclusive);
    }

    public function getEffectiveTaxRates(int $organizationId, string $type = 'sales', ?string $date = null): Collection
    {
        $query = TaxRate::where('organization_id', $organizationId)
            ->active()
            ->effective($date);

        if ($type === 'sales') {
            $query->forSales();
        } else {
            $query->forPurchase();
        }

        return $query->orderBy('name')->get();
    }

    public function getDefaultTaxRate(int $organizationId, string $type = 'sales'): ?TaxRate
    {
        return TaxRate::where('organization_id', $organizationId)
            ->where('type', $type)
            ->where('is_default', true)
            ->active()
            ->effective()
            ->first();
    }

    public function calculateInvoiceTaxes(array $lines, ?int $defaultTaxRateId = null): array
    {
        $taxSummary = [];
        $totalNetAmount = 0;
        $totalTaxAmount = 0;
        $totalGrossAmount = 0;

        foreach ($lines as $line) {
            $amount = $line['quantity'] * $line['unit_price'];
            $discount = $line['discount'] ?? 0;
            $netAmount = $amount - $discount;
            $taxRateId = $line['tax_rate_id'] ?? $defaultTaxRateId;

            $totalNetAmount += $netAmount;

            if ($taxRateId) {
                $taxRate = TaxRate::find($taxRateId);
                if ($taxRate && $taxRate->is_active) {
                    $calculation = $taxRate->calculateTax($netAmount);
                    $taxAmount = $calculation['tax_amount'];
                    $totalTaxAmount += $taxAmount;

                    // Group by tax rate
                    if (!isset($taxSummary[$taxRateId])) {
                        $taxSummary[$taxRateId] = [
                            'tax_rate_id' => $taxRateId,
                            'tax_name' => $taxRate->name,
                            'rate' => $taxRate->rate,
                            'taxable_amount' => 0,
                            'tax_amount' => 0,
                        ];
                    }
                    $taxSummary[$taxRateId]['taxable_amount'] += $netAmount;
                    $taxSummary[$taxRateId]['tax_amount'] += $taxAmount;
                }
            }
        }

        $totalGrossAmount = $totalNetAmount + $totalTaxAmount;

        return [
            'subtotal' => round($totalNetAmount, 2),
            'total_tax' => round($totalTaxAmount, 2),
            'grand_total' => round($totalGrossAmount, 2),
            'tax_breakdown' => array_values($taxSummary),
        ];
    }

    public function getTaxReport(int $organizationId, string $from, string $to, ?string $type = null): array
    {
        // This would typically query invoices/transactions
        // For now, return structure
        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'sales_tax' => [
                'taxable_sales' => 0,
                'tax_collected' => 0,
                'exempt_sales' => 0,
                'zero_rated_sales' => 0,
            ],
            'purchase_tax' => [
                'taxable_purchases' => 0,
                'tax_paid' => 0,
                'exempt_purchases' => 0,
            ],
            'withholding_tax' => [
                'total_withheld' => 0,
            ],
            'net_tax_payable' => 0,
            'breakdown_by_rate' => [],
        ];
    }

    public function seedDefaultTaxRates(int $organizationId): void
    {
        $defaults = [
            [
                'code' => 'VAT15',
                'name' => 'VAT 15%',
                'rate' => 15.00,
                'type' => TaxRate::TYPE_SALES,
                'is_default' => true,
            ],
            [
                'code' => 'VAT5',
                'name' => 'VAT 5%',
                'rate' => 5.00,
                'type' => TaxRate::TYPE_SALES,
            ],
            [
                'code' => 'ZERO',
                'name' => 'Zero Rated',
                'rate' => 0,
                'type' => TaxRate::TYPE_ZERO_RATED,
            ],
            [
                'code' => 'EXEMPT',
                'name' => 'Exempt',
                'rate' => 0,
                'type' => TaxRate::TYPE_EXEMPT,
            ],
            [
                'code' => 'WHT5',
                'name' => 'Withholding Tax 5%',
                'rate' => 5.00,
                'type' => TaxRate::TYPE_WITHHOLDING,
            ],
        ];

        foreach ($defaults as $tax) {
            TaxRate::firstOrCreate(
                ['organization_id' => $organizationId, 'code' => $tax['code']],
                array_merge($tax, ['organization_id' => $organizationId, 'is_active' => true])
            );
        }
    }
}
