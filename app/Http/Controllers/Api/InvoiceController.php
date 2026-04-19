<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Invoice\StoreInvoiceRequest;
use App\Http\Requests\Invoice\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends BaseApiController
{
    public function __construct(
        protected InvoiceService $invoiceService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->invoiceService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        $invoice = $this->invoiceService->create($request->validated());

        return $this->createdResponse(
            new InvoiceResource($invoice)
        );
    }

    public function show(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->findById($id);

        if (!$invoice) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new InvoiceResource($invoice)
        );
    }

    public function update(UpdateInvoiceRequest $request, int $id): JsonResponse
    {
        if (!$this->invoiceService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->invoiceService->update($id, $request->validated());
        $invoice = $this->invoiceService->findById($id);

        return $this->successResponse(
            new InvoiceResource($invoice),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->invoiceService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->invoiceService->delete($id);

        return $this->noContentResponse();
    }
}
