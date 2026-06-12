<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Invoice;
use App\Models\JournalBatch;
use App\Models\Product;
use App\Models\SerialNumber;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    // ==================== Stock Movement ====================
    public function recordMovement(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $balance = StockBalance::getOrCreate(
                $data['warehouse_id'],
                $data['product_id'],
                $data['organization_id']
            );

            $balanceBefore = $balance->quantity;

            // Update balance
            if ($data['quantity'] > 0) {
                $balance->addStock($data['quantity'], $data['unit_cost'] ?? null);
            } else {
                $balance->removeStock(abs($data['quantity']));
            }

            // Create movement record
            return StockMovement::create([
                'organization_id' => $data['organization_id'],
                'warehouse_id' => $data['warehouse_id'],
                'product_id' => $data['product_id'],
                'batch_id' => $data['batch_id'] ?? null,
                'serial_number_id' => $data['serial_number_id'] ?? null,
                'type' => $data['type'],
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? null,
                'total_cost' => ($data['unit_cost'] ?? 0) * abs($data['quantity']),
                'balance_before' => $balanceBefore,
                'balance_after' => $balance->quantity,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });
    }

    // ==================== Purchase Integration ====================
    public function processPurchaseInvoice(Invoice $invoice): void
    {
        if ($invoice->type !== 'purchase' || $invoice->status !== 'approved') {
            return;
        }

        DB::transaction(function () use ($invoice) {
            foreach ($invoice->lines as $line) {
                $product = $line->product;

                if (!$product || !$product->track_inventory) {
                    continue;
                }

                $warehouseId = $line->warehouse_id ?? $invoice->branch?->default_warehouse_id;
                if (!$warehouseId) {
                    continue;
                }

                // Handle based on tracking type
                if ($product->tracking_type === 'batch') {
                    $this->createBatchFromPurchase($invoice, $line, $warehouseId);
                } elseif ($product->tracking_type === 'serial') {
                    $this->createSerialsFromPurchase($invoice, $line, $warehouseId);
                } else {
                    // Simple stock update
                    $this->recordMovement([
                        'organization_id' => $invoice->organization_id,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'type' => StockMovement::TYPE_PURCHASE,
                        'quantity' => $line->quantity,
                        'unit_cost' => $line->unit_price,
                        'reference_type' => Invoice::class,
                        'reference_id' => $invoice->id,
                    ]);
                }
            }

            // Create accounting entry
            $this->createPurchaseJournalEntry($invoice);
        });
    }

    protected function createBatchFromPurchase(Invoice $invoice, $line, int $warehouseId): void
    {
        $batch = Batch::create([
            'organization_id' => $invoice->organization_id,
            'product_id' => $line->product_id,
            'warehouse_id' => $warehouseId,
            'batch_number' => $line->batch_number ?? Batch::generateBatchNumber($line->product_id),
            'quantity' => $line->quantity,
            'manufacturing_date' => $line->manufacturing_date ?? null,
            'expiry_date' => $line->expiry_date ?? null,
            'cost_per_unit' => $line->unit_price,
            'purchase_invoice_id' => $invoice->id,
        ]);

        $this->recordMovement([
            'organization_id' => $invoice->organization_id,
            'warehouse_id' => $warehouseId,
            'product_id' => $line->product_id,
            'batch_id' => $batch->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity' => $line->quantity,
            'unit_cost' => $line->unit_price,
            'reference_type' => Invoice::class,
            'reference_id' => $invoice->id,
        ]);
    }

    protected function createSerialsFromPurchase(Invoice $invoice, $line, int $warehouseId): void
    {
        $serialNumbers = $line->serial_numbers ?? [];
        $quantity = (int) $line->quantity;

        // Auto-generate if not provided
        if (empty($serialNumbers)) {
            for ($i = 0; $i < $quantity; $i++) {
                $serialNumbers[] = SerialNumber::generateSerial($line->product_id);
            }
        }

        foreach ($serialNumbers as $serial) {
            $serialRecord = SerialNumber::create([
                'organization_id' => $invoice->organization_id,
                'product_id' => $line->product_id,
                'warehouse_id' => $warehouseId,
                'serial_number' => $serial,
                'status' => SerialNumber::STATUS_AVAILABLE,
                'purchase_invoice_id' => $invoice->id,
                'purchase_date' => $invoice->date,
                'warranty_expiry' => $line->warranty_months 
                    ? now()->addMonths($line->warranty_months) 
                    : null,
            ]);

            $this->recordMovement([
                'organization_id' => $invoice->organization_id,
                'warehouse_id' => $warehouseId,
                'product_id' => $line->product_id,
                'serial_number_id' => $serialRecord->id,
                'type' => StockMovement::TYPE_PURCHASE,
                'quantity' => 1,
                'unit_cost' => $line->unit_price,
                'reference_type' => Invoice::class,
                'reference_id' => $invoice->id,
            ]);
        }
    }

    // ==================== Sales Integration ====================
    public function processSalesInvoice(Invoice $invoice): void
    {
        if ($invoice->type !== 'sales' || $invoice->status !== 'approved') {
            return;
        }

        DB::transaction(function () use ($invoice) {
            foreach ($invoice->lines as $line) {
                $product = $line->product;

                if (!$product || !$product->track_inventory) {
                    continue;
                }

                $warehouseId = $line->warehouse_id ?? $invoice->branch?->default_warehouse_id;
                if (!$warehouseId) {
                    continue;
                }

                if ($product->tracking_type === 'serial') {
                    $this->processSalesSerials($invoice, $line, $warehouseId);
                } elseif ($product->tracking_type === 'batch') {
                    $this->processSalesBatch($invoice, $line, $warehouseId);
                } else {
                    $this->recordMovement([
                        'organization_id' => $invoice->organization_id,
                        'warehouse_id' => $warehouseId,
                        'product_id' => $product->id,
                        'type' => StockMovement::TYPE_SALE,
                        'quantity' => -$line->quantity,
                        'reference_type' => Invoice::class,
                        'reference_id' => $invoice->id,
                    ]);
                }
            }

            // Create accounting entry
            $this->createSalesJournalEntry($invoice);
        });
    }

    protected function processSalesSerials(Invoice $invoice, $line, int $warehouseId): void
    {
        $serialNumbers = $line->serial_numbers ?? [];

        foreach ($serialNumbers as $serial) {
            $serialRecord = SerialNumber::where('serial_number', $serial)
                ->where('product_id', $line->product_id)
                ->available()
                ->first();

            if ($serialRecord) {
                $serialRecord->sell($invoice->id);

                $this->recordMovement([
                    'organization_id' => $invoice->organization_id,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $line->product_id,
                    'serial_number_id' => $serialRecord->id,
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => -1,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                ]);
            }
        }
    }

    protected function processSalesBatch(Invoice $invoice, $line, int $warehouseId): void
    {
        $batchId = $line->batch_id;
        $quantity = $line->quantity;

        if ($batchId) {
            // Specific batch
            $batch = Batch::find($batchId);
            if ($batch) {
                $batch->deduct($quantity);

                $this->recordMovement([
                    'organization_id' => $invoice->organization_id,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $line->product_id,
                    'batch_id' => $batch->id,
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => -$quantity,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                ]);
            }
        } else {
            // FIFO - use oldest batches first
            $batches = Batch::forProduct($line->product_id)
                ->inWarehouse($warehouseId)
                ->active()
                ->withAvailableStock()
                ->orderBy('created_at')
                ->get();

            $remaining = $quantity;
            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                $deductQty = min($remaining, $batch->getAvailableQuantity());
                $batch->deduct($deductQty);

                $this->recordMovement([
                    'organization_id' => $invoice->organization_id,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $line->product_id,
                    'batch_id' => $batch->id,
                    'type' => StockMovement::TYPE_SALE,
                    'quantity' => -$deductQty,
                    'reference_type' => Invoice::class,
                    'reference_id' => $invoice->id,
                ]);

                $remaining -= $deductQty;
            }
        }
    }

    // ==================== Journal Entries ====================
    protected function createPurchaseJournalEntry(Invoice $invoice): void
    {
        $inventoryAccountId = config('accounting.accounts.inventory');
        $vatReceivableAccountId = config('accounting.accounts.vat_receivable');
        $payableAccountId = config('accounting.accounts.accounts_payable');

        if (!$inventoryAccountId || !$payableAccountId) {
            return;
        }

        $lines = [
            [
                'account_id' => $inventoryAccountId,
                'debit' => $invoice->subtotal,
                'credit' => 0,
                'description' => 'Inventory - ' . $invoice->number,
            ],
        ];

        if ($invoice->total_tax > 0 && $vatReceivableAccountId) {
            $lines[] = [
                'account_id' => $vatReceivableAccountId,
                'debit' => $invoice->total_tax,
                'credit' => 0,
                'description' => 'VAT Receivable - ' . $invoice->number,
            ];
        }

        $lines[] = [
            'account_id' => $invoice->party?->payable_account_id ?? $payableAccountId,
            'debit' => 0,
            'credit' => $invoice->grand_total,
            'description' => 'Accounts Payable - ' . $invoice->party?->name,
            'party_id' => $invoice->party_id,
        ];

        $this->journalService->createFromSource($invoice, $lines, "Purchase Invoice {$invoice->number}");
    }

    protected function createSalesJournalEntry(Invoice $invoice): void
    {
        $receivableAccountId = config('accounting.accounts.accounts_receivable');
        $revenueAccountId = config('accounting.accounts.sales_revenue');
        $vatPayableAccountId = config('accounting.accounts.vat_payable');
        $cogsAccountId = config('accounting.accounts.cost_of_goods_sold');
        $inventoryAccountId = config('accounting.accounts.inventory');

        if (!$receivableAccountId || !$revenueAccountId) {
            return;
        }

        $lines = [
            [
                'account_id' => $invoice->party?->receivable_account_id ?? $receivableAccountId,
                'debit' => $invoice->grand_total,
                'credit' => 0,
                'description' => 'Accounts Receivable - ' . $invoice->party?->name,
                'party_id' => $invoice->party_id,
            ],
            [
                'account_id' => $revenueAccountId,
                'debit' => 0,
                'credit' => $invoice->subtotal,
                'description' => 'Sales Revenue - ' . $invoice->number,
            ],
        ];

        if ($invoice->total_tax > 0 && $vatPayableAccountId) {
            $lines[] = [
                'account_id' => $vatPayableAccountId,
                'debit' => 0,
                'credit' => $invoice->total_tax,
                'description' => 'VAT Payable - ' . $invoice->number,
            ];
        }

        // COGS entry (if configured)
        if ($cogsAccountId && $inventoryAccountId) {
            $cogs = $this->calculateCOGS($invoice);
            if ($cogs > 0) {
                $lines[] = [
                    'account_id' => $cogsAccountId,
                    'debit' => $cogs,
                    'credit' => 0,
                    'description' => 'Cost of Goods Sold - ' . $invoice->number,
                ];
                $lines[] = [
                    'account_id' => $inventoryAccountId,
                    'debit' => 0,
                    'credit' => $cogs,
                    'description' => 'Inventory - ' . $invoice->number,
                ];
            }
        }

        $this->journalService->createFromSource($invoice, $lines, "Sales Invoice {$invoice->number}");
    }

    protected function calculateCOGS(Invoice $invoice): float
    {
        $cogs = 0;

        foreach ($invoice->lines as $line) {
            $product = $line->product;
            if (!$product || !$product->track_inventory) {
                continue;
            }

            // Use average cost from stock balance
            $balance = StockBalance::forProduct($product->id)->first();
            if ($balance && $balance->average_cost) {
                $cogs += $line->quantity * $balance->average_cost;
            }
        }

        return $cogs;
    }

    // ==================== Stock Queries ====================
    public function getStockLevel(int $productId, ?int $warehouseId = null): float
    {
        $query = StockBalance::forProduct($productId);

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->sum('available_quantity');
    }

    public function getAvailableSerials(int $productId, ?int $warehouseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = SerialNumber::forProduct($productId)->available();

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->get();
    }

    public function getAvailableBatches(int $productId, ?int $warehouseId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Batch::forProduct($productId)
            ->active()
            ->notExpired()
            ->withAvailableStock();

        if ($warehouseId) {
            $query->inWarehouse($warehouseId);
        }

        return $query->orderBy('expiry_date')->orderBy('created_at')->get();
    }

    public function getExpiringBatches(int $organizationId, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return Batch::where('organization_id', $organizationId)
            ->active()
            ->expiringSoon($days)
            ->with(['product:id,name,sku', 'warehouse:id,name'])
            ->orderBy('expiry_date')
            ->get();
    }
}
