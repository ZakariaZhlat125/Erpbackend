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

    public function approve(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->findById($id);

        if (!$invoice) {
            return $this->notFoundResponse();
        }

        if (!$invoice->isDraft()) {
            return $this->errorResponse(
                'Only draft invoices can be approved',
                409
            );
        }

        // TODO: Implement ApproveInvoiceAction
        // $approvedInvoice = app(ApproveInvoiceAction::class)->execute($invoice, auth()->user());

        return $this->successResponse(
            new InvoiceResource($invoice),
            'Invoice approved successfully'
        );
    }

    public function cancel(int $id): JsonResponse
    {
        $invoice = $this->invoiceService->findById($id);

        if (!$invoice) {
            return $this->notFoundResponse();
        }

        if ($invoice->status === 'cancelled') {
            return $this->errorResponse(
                'Invoice is already cancelled',
                409
            );
        }

        // TODO: Implement CancelInvoiceAction
        // $cancelledInvoice = app(CancelInvoiceAction::class)->execute($invoice, auth()->user());

        return $this->successResponse(
            new InvoiceResource($invoice),
            'Invoice cancelled successfully'
        );
    }

    public function pdf(int $id): mixed
    {
        $invoice = $this->invoiceService->findById($id);

        if (!$invoice) {
            return $this->notFoundResponse();
        }

        // TODO: Implement PDF generation
        // $pdf = app(InvoicePdfGenerator::class)->generate($invoice);
        // return $pdf->stream("invoice-{$invoice->number}.pdf");

        return $this->errorResponse('PDF generation not implemented yet', 501);
    }

    public function bulkApprove(): JsonResponse
    {
        $validated = request()->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
        ]);

        $result = $this->invoiceService->bulkApprove($validated['invoice_ids']);

        return $this->successResponse($result, 'Bulk approval completed');
    }

    public function bulkDelete(): JsonResponse
    {
        $validated = request()->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'required|integer|exists:invoices,id',
        ]);

        $invoices = $this->invoiceService->findByIds($validated['invoice_ids']);
        $deleted = [];
        $failed = [];

        foreach ($invoices as $invoice) {
            if ($invoice->status !== 'draft') {
                $failed[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'reason' => 'Only draft invoices can be deleted'
                ];
                continue;
            }

            try {
                $this->invoiceService->delete($invoice->id);
                $deleted[] = $invoice->id;
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'reason' => 'Delete failed'
                ];
            }
        }

        return $this->successResponse([
            'deleted' => $deleted,
            'failed' => $failed,
            'total_deleted' => count($deleted),
            'total_failed' => count($failed),
        ], 'Bulk delete completed');
    }

    public function statistics(): JsonResponse
    {
        $type = request()->query('type');
        $stats = $this->invoiceService->getStatistics($type);

        return $this->successResponse($stats, 'Invoice statistics retrieved');
    }

    public function search(): JsonResponse
    {
        $criteria = request()->only([
            'number_like',
            'type',
            'status',
            'party_id',
            'issue_date_from',
            'issue_date_to',
            'due_date_from',
            'due_date_to',
            'grand_total_from',
            'grand_total_to',
        ]);

        $perPage = request()->integer('per_page', 15);
        $results = $this->invoiceService->search($criteria, $perPage);

        return $this->paginatedResponse($results);
    }

    public function duplicate(int $id): JsonResponse
    {
        try {
            $newInvoice = $this->invoiceService->duplicate($id);

            return $this->createdResponse(
                new InvoiceResource($newInvoice),
                'Invoice duplicated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function export(): mixed
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        // $invoices = $this->invoiceService->getAll();
        // return Excel::download(new InvoicesExport($invoices), 'invoices.xlsx');

        return $this->errorResponse('Export functionality not implemented yet', 501);
    }
}
