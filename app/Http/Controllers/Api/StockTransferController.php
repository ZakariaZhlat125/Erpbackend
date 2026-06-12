<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\StockTransferResource;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockTransferController extends BaseApiController
{
    public function __construct(
        protected StockTransferService $transferService
    ) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['status', 'from_warehouse_id', 'to_warehouse_id', 'search', 'per_page']);
        
        $transfers = $this->transferService->getTransfers(
            Auth::user()->organization_id,
            $filters
        );

        return $this->paginatedResponse($transfers, StockTransferResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|exists:warehouses,id|different:from_warehouse_id',
            'request_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:request_date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.batch_id' => 'nullable|exists:batches,id',
            'lines.*.quantity_requested' => 'required|numeric|min:0.0001',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
            'lines.*.notes' => 'nullable|string',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;

        try {
            $transfer = $this->transferService->create($validated);

            return $this->createdResponse(
                new StockTransferResource($transfer),
                'Stock transfer created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $transfer = StockTransfer::with([
            'fromWarehouse:id,name',
            'toWarehouse:id,name',
            'lines.product:id,name,sku',
            'lines.batch',
            'requestedBy:id,name',
            'approvedBy:id,name',
        ])->find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new StockTransferResource($transfer));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'expected_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'lines' => 'sometimes|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.batch_id' => 'nullable|exists:batches,id',
            'lines.*.quantity_requested' => 'required|numeric|min:0.0001',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        try {
            $transfer = $this->transferService->update($transfer, $validated);

            return $this->successResponse(
                new StockTransferResource($transfer),
                'Stock transfer updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function submit(int $id): JsonResponse
    {
        $transfer = StockTransfer::find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        try {
            $transfer = $this->transferService->submit($transfer);

            return $this->successResponse(
                new StockTransferResource($transfer),
                'Stock transfer submitted for approval'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function approve(int $id): JsonResponse
    {
        $transfer = StockTransfer::find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        try {
            $transfer = $this->transferService->approve($transfer);

            return $this->successResponse(
                new StockTransferResource($transfer),
                'Stock transfer approved'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function ship(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        $shippedQuantities = $request->input('shipped_quantities', []);

        try {
            $transfer = $this->transferService->ship($transfer, $shippedQuantities);

            return $this->successResponse(
                new StockTransferResource($transfer),
                'Stock transfer shipped'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function receive(Request $request, int $id): JsonResponse
    {
        $transfer = StockTransfer::find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        $receivedQuantities = $request->input('received_quantities', []);

        try {
            $transfer = $this->transferService->receive($transfer, $receivedQuantities);

            return $this->successResponse(
                new StockTransferResource($transfer),
                'Stock transfer received'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function cancel(int $id): JsonResponse
    {
        $transfer = StockTransfer::find($id);

        if (!$transfer) {
            return $this->notFoundResponse();
        }

        try {
            $transfer = $this->transferService->cancel($transfer);

            return $this->successResponse(
                new StockTransferResource($transfer),
                'Stock transfer cancelled'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
