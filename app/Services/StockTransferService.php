<?php

namespace App\Services;

use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function create(array $data): StockTransfer
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['number'] = StockTransfer::generateNumber($data['organization_id']);
            $data['requested_by'] = auth()->id();

            $transfer = StockTransfer::create($data);

            foreach ($lines as $lineData) {
                $lineData['transfer_id'] = $transfer->id;
                StockTransferLine::create($lineData);
            }

            return $transfer->load('lines.product');
        });
    }

    public function update(StockTransfer $transfer, array $data): StockTransfer
    {
        if (!$transfer->canBeEdited()) {
            throw new \Exception('Transfer cannot be edited in current status');
        }

        return DB::transaction(function () use ($transfer, $data) {
            $lines = $data['lines'] ?? null;
            unset($data['lines']);

            $transfer->update($data);

            if ($lines !== null) {
                $transfer->lines()->delete();

                foreach ($lines as $lineData) {
                    $lineData['transfer_id'] = $transfer->id;
                    StockTransferLine::create($lineData);
                }
            }

            return $transfer->fresh('lines.product');
        });
    }

    public function submit(StockTransfer $transfer): StockTransfer
    {
        if (!$transfer->isDraft()) {
            throw new \Exception('Only draft transfers can be submitted');
        }

        // Validate stock availability
        foreach ($transfer->lines as $line) {
            $available = $this->inventoryService->getStockLevel(
                $line->product_id,
                $transfer->from_warehouse_id
            );

            if ($available < $line->quantity_requested) {
                throw new \Exception("Insufficient stock for {$line->product->name}. Available: {$available}");
            }
        }

        $transfer->submit();

        return $transfer->fresh();
    }

    public function approve(StockTransfer $transfer): StockTransfer
    {
        if (!$transfer->canBeApproved()) {
            throw new \Exception('Transfer cannot be approved in current status');
        }

        // Reserve stock
        foreach ($transfer->lines as $line) {
            $balance = \App\Models\StockBalance::where('warehouse_id', $transfer->from_warehouse_id)
                ->where('product_id', $line->product_id)
                ->first();

            if ($balance) {
                $balance->reserve($line->quantity_requested);
            }
        }

        $transfer->approve(auth()->id());

        return $transfer->fresh();
    }

    public function ship(StockTransfer $transfer, array $shippedQuantities = []): StockTransfer
    {
        if (!$transfer->canBeShipped()) {
            throw new \Exception('Transfer cannot be shipped in current status');
        }

        return DB::transaction(function () use ($transfer, $shippedQuantities) {
            foreach ($transfer->lines as $line) {
                $shippedQty = $shippedQuantities[$line->id] ?? $line->quantity_requested;
                $line->update(['quantity_shipped' => $shippedQty]);

                // Create outbound movement
                $this->inventoryService->recordMovement([
                    'organization_id' => $transfer->organization_id,
                    'warehouse_id' => $transfer->from_warehouse_id,
                    'product_id' => $line->product_id,
                    'batch_id' => $line->batch_id,
                    'type' => StockMovement::TYPE_TRANSFER_OUT,
                    'quantity' => -$shippedQty,
                    'unit_cost' => $line->unit_cost,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Transfer to {$transfer->toWarehouse->name}",
                ]);

                // Release reservation
                $balance = \App\Models\StockBalance::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $line->product_id)
                    ->first();

                if ($balance) {
                    $balance->releaseReservation($line->quantity_requested);
                }
            }

            $transfer->ship(auth()->id());

            return $transfer->fresh('lines');
        });
    }

    public function receive(StockTransfer $transfer, array $receivedQuantities = []): StockTransfer
    {
        if (!$transfer->canBeReceived()) {
            throw new \Exception('Transfer cannot be received in current status');
        }

        return DB::transaction(function () use ($transfer, $receivedQuantities) {
            foreach ($transfer->lines as $line) {
                $receivedQty = $receivedQuantities[$line->id] ?? $line->quantity_shipped;
                $line->update(['quantity_received' => $receivedQty]);

                // Create inbound movement
                $this->inventoryService->recordMovement([
                    'organization_id' => $transfer->organization_id,
                    'warehouse_id' => $transfer->to_warehouse_id,
                    'product_id' => $line->product_id,
                    'batch_id' => $line->batch_id,
                    'type' => StockMovement::TYPE_TRANSFER_IN,
                    'quantity' => $receivedQty,
                    'unit_cost' => $line->unit_cost,
                    'reference_type' => StockTransfer::class,
                    'reference_id' => $transfer->id,
                    'notes' => "Transfer from {$transfer->fromWarehouse->name}",
                ]);

                // Handle variance (if any)
                $variance = $line->getVariance();
                if ($variance < 0) {
                    // Shortage - record adjustment
                    $this->inventoryService->recordMovement([
                        'organization_id' => $transfer->organization_id,
                        'warehouse_id' => $transfer->from_warehouse_id,
                        'product_id' => $line->product_id,
                        'type' => StockMovement::TYPE_ADJUSTMENT,
                        'quantity' => $variance, // Negative
                        'reference_type' => StockTransfer::class,
                        'reference_id' => $transfer->id,
                        'notes' => "Transfer variance - shortage",
                    ]);
                }
            }

            $transfer->receive(auth()->id());

            return $transfer->fresh('lines');
        });
    }

    public function cancel(StockTransfer $transfer): StockTransfer
    {
        if (in_array($transfer->status, [StockTransfer::STATUS_RECEIVED, StockTransfer::STATUS_CANCELLED])) {
            throw new \Exception('Transfer cannot be cancelled');
        }

        // Release any reservations
        if ($transfer->status === StockTransfer::STATUS_APPROVED) {
            foreach ($transfer->lines as $line) {
                $balance = \App\Models\StockBalance::where('warehouse_id', $transfer->from_warehouse_id)
                    ->where('product_id', $line->product_id)
                    ->first();

                if ($balance) {
                    $balance->releaseReservation($line->quantity_requested);
                }
            }
        }

        $transfer->cancel();

        return $transfer->fresh();
    }

    public function getTransfers(int $organizationId, array $filters = []): LengthAwarePaginator
    {
        $query = StockTransfer::where('organization_id', $organizationId)
            ->with(['fromWarehouse:id,name', 'toWarehouse:id,name', 'requestedBy:id,name']);

        if (isset($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (isset($filters['from_warehouse_id'])) {
            $query->where('from_warehouse_id', $filters['from_warehouse_id']);
        }

        if (isset($filters['to_warehouse_id'])) {
            $query->where('to_warehouse_id', $filters['to_warehouse_id']);
        }

        if (isset($filters['search'])) {
            $query->where('number', 'like', "%{$filters['search']}%");
        }

        return $query->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }
}
