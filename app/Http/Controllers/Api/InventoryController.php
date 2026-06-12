<?php

namespace App\Http\Controllers\Api;

use App\Models\Batch;
use App\Models\SerialNumber;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends BaseApiController
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    // ==================== Serial Numbers ====================
    public function serials(Request $request): JsonResponse
    {
        $query = SerialNumber::where('organization_id', Auth::user()->organization_id)
            ->with(['product:id,name,sku', 'warehouse:id,name']);

        if ($productId = $request->input('product_id')) {
            $query->forProduct($productId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->inWarehouse($warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('serial_number', 'like', "%{$search}%");
        }

        $serials = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginatedResponse($serials);
    }

    public function availableSerials(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');

        if (!$productId) {
            return $this->errorResponse('product_id is required', 422);
        }

        $serials = $this->inventoryService->getAvailableSerials($productId, $warehouseId);

        return $this->successResponse($serials->map(fn($s) => [
            'id' => $s->id,
            'serial_number' => $s->serial_number,
            'warehouse_id' => $s->warehouse_id,
            'warehouse' => $s->warehouse?->name,
            'warranty_expiry' => $s->warranty_expiry?->format('Y-m-d'),
        ]));
    }

    public function serialDetails(int $id): JsonResponse
    {
        $serial = SerialNumber::with([
            'product:id,name,sku',
            'warehouse:id,name',
            'purchaseInvoice:id,number,date',
            'salesInvoice:id,number,date',
        ])->find($id);

        if (!$serial) {
            return $this->notFoundResponse();
        }

        // Get movement history
        $movements = StockMovement::where('serial_number_id', $id)
            ->with('createdBy:id,name')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse([
            'serial' => $serial,
            'movements' => $movements,
        ]);
    }

    // ==================== Batches ====================
    public function batches(Request $request): JsonResponse
    {
        $query = Batch::where('organization_id', Auth::user()->organization_id)
            ->with(['product:id,name,sku', 'warehouse:id,name']);

        if ($productId = $request->input('product_id')) {
            $query->forProduct($productId);
        }

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->inWarehouse($warehouseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where('batch_number', 'like', "%{$search}%");
        }

        $batches = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginatedResponse($batches);
    }

    public function availableBatches(Request $request): JsonResponse
    {
        $productId = $request->input('product_id');
        $warehouseId = $request->input('warehouse_id');

        if (!$productId) {
            return $this->errorResponse('product_id is required', 422);
        }

        $batches = $this->inventoryService->getAvailableBatches($productId, $warehouseId);

        return $this->successResponse($batches->map(fn($b) => [
            'id' => $b->id,
            'batch_number' => $b->batch_number,
            'available_quantity' => $b->getAvailableQuantity(),
            'expiry_date' => $b->expiry_date?->format('Y-m-d'),
            'days_until_expiry' => $b->getDaysUntilExpiry(),
            'warehouse_id' => $b->warehouse_id,
            'cost_per_unit' => $b->cost_per_unit,
        ]));
    }

    public function expiringBatches(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        $batches = $this->inventoryService->getExpiringBatches(
            Auth::user()->organization_id,
            $days
        );

        return $this->successResponse($batches->map(fn($b) => [
            'id' => $b->id,
            'batch_number' => $b->batch_number,
            'product' => $b->product->name,
            'warehouse' => $b->warehouse?->name,
            'quantity' => $b->quantity,
            'expiry_date' => $b->expiry_date?->format('Y-m-d'),
            'days_until_expiry' => $b->getDaysUntilExpiry(),
        ]));
    }

    public function batchDetails(int $id): JsonResponse
    {
        $batch = Batch::with([
            'product:id,name,sku',
            'warehouse:id,name',
            'purchaseInvoice:id,number,date',
        ])->find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        // Get movement history
        $movements = StockMovement::where('batch_id', $id)
            ->with('createdBy:id,name')
            ->orderByDesc('created_at')
            ->get();

        return $this->successResponse([
            'batch' => $batch,
            'available_quantity' => $batch->getAvailableQuantity(),
            'days_until_expiry' => $batch->getDaysUntilExpiry(),
            'movements' => $movements,
        ]);
    }

    // ==================== Stock Balances ====================
    public function stockBalances(Request $request): JsonResponse
    {
        $query = StockBalance::where('organization_id', Auth::user()->organization_id)
            ->with(['product:id,name,sku', 'warehouse:id,name']);

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->inWarehouse($warehouseId);
        }

        if ($productId = $request->input('product_id')) {
            $query->forProduct($productId);
        }

        if ($request->boolean('low_stock')) {
            $query->lowStock();
        }

        if ($request->boolean('out_of_stock')) {
            $query->outOfStock();
        }

        $balances = $query->orderBy('product_id')
            ->paginate($request->integer('per_page', 25));

        return $this->paginatedResponse($balances);
    }

    // ==================== Stock Movements ====================
    public function stockMovements(Request $request): JsonResponse
    {
        $query = StockMovement::where('organization_id', Auth::user()->organization_id)
            ->with([
                'product:id,name,sku',
                'warehouse:id,name',
                'batch:id,batch_number',
                'serialNumber:id,serial_number',
                'createdBy:id,name',
            ]);

        if ($warehouseId = $request->input('warehouse_id')) {
            $query->inWarehouse($warehouseId);
        }

        if ($productId = $request->input('product_id')) {
            $query->forProduct($productId);
        }

        if ($type = $request->input('type')) {
            $query->byType($type);
        }

        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to);
        }

        $movements = $query->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return $this->paginatedResponse($movements);
    }

    // ==================== Adjust Stock ====================
    public function adjustStock(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|numeric',
            'batch_id' => 'nullable|exists:batches,id',
            'serial_number_id' => 'nullable|exists:serial_numbers,id',
            'notes' => 'nullable|string',
        ]);

        try {
            $movement = $this->inventoryService->recordMovement([
                'organization_id' => Auth::user()->organization_id,
                'warehouse_id' => $validated['warehouse_id'],
                'product_id' => $validated['product_id'],
                'batch_id' => $validated['batch_id'] ?? null,
                'serial_number_id' => $validated['serial_number_id'] ?? null,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return $this->createdResponse($movement, 'Stock adjusted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
