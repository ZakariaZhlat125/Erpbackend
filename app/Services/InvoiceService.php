<?php

namespace App\Services;

use App\Models\Invoice;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class InvoiceService extends BaseService
{
    public function __construct(InvoiceRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function bulkApprove(array $invoiceIds): array
    {
        $invoices = $this->findByIds($invoiceIds);
        $approved = [];
        $failed = [];

        foreach ($invoices as $invoice) {
            if ($invoice->status !== 'draft') {
                $failed[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'reason' => 'Only draft invoices can be approved'
                ];
                continue;
            }

            try {
                $invoice->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $approved[] = $invoice->id;
            } catch (\Exception $e) {
                $failed[] = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'reason' => 'Update failed'
                ];
            }
        }

        return [
            'approved' => $approved,
            'failed' => $failed,
            'total_approved' => count($approved),
            'total_failed' => count($failed),
        ];
    }

    public function getStatistics(?string $type = null): array
    {
        $query = Invoice::query();
        
        if ($type) {
            $query->where('type', $type);
        }

        $stats = [
            'total_count' => (clone $query)->count(),
            'draft_count' => (clone $query)->where('status', 'draft')->count(),
            'approved_count' => (clone $query)->where('status', 'approved')->count(),
            'paid_count' => (clone $query)->where('status', 'paid')->count(),
            'cancelled_count' => (clone $query)->where('status', 'cancelled')->count(),
            'overdue_count' => (clone $query)->where('status', '!=', 'paid')->where('due_date', '<', now())->count(),
            'total_amount' => (clone $query)->sum('grand_total'),
            'draft_amount' => (clone $query)->where('status', 'draft')->sum('grand_total'),
            'approved_amount' => (clone $query)->where('status', 'approved')->sum('grand_total'),
            'paid_amount' => (clone $query)->where('status', 'paid')->sum('grand_total'),
            'overdue_amount' => (clone $query)->where('status', '!=', 'paid')->where('due_date', '<', now())->sum('grand_total'),
        ];

        $monthlyTrends = (clone $query)
            ->select(
                DB::raw('DATE_FORMAT(issue_date, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(grand_total) as total')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        $stats['monthly_trends'] = $monthlyTrends;

        return $stats;
    }

    public function duplicate(int $id): Invoice
    {
        $original = $this->findById($id);
        
        if (!$original) {
            throw new \Exception('Invoice not found');
        }

        $data = $original->toArray();
        unset($data['id'], $data['number'], $data['created_at'], $data['updated_at'], $data['deleted_at']);
        $data['status'] = 'draft';
        $data['approved_by'] = null;
        $data['approved_at'] = null;
        $data['issue_date'] = now()->format('Y-m-d');
        $data['due_date'] = now()->addDays(30)->format('Y-m-d');

        return $this->create($data);
    }
}
