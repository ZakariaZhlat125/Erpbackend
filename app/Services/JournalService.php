<?php

namespace App\Services;

use App\Models\JournalBatch;
use App\Models\JournalLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class JournalService
{
    public function createBatch(array $data): JournalBatch
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['number'] = $data['number'] ?? JournalBatch::generateNumber($data['organization_id']);
            $data['created_by'] = auth()->id();

            $batch = JournalBatch::create($data);

            foreach ($lines as $index => $lineData) {
                $lineData['batch_id'] = $batch->id;
                $lineData['line_number'] = $lineData['line_number'] ?? ($index + 1);
                JournalLine::create($lineData);
            }

            $batch->recalculateTotals();

            return $batch->fresh('lines');
        });
    }

    public function updateBatch(JournalBatch $batch, array $data): JournalBatch
    {
        if (!$batch->canBeEdited()) {
            throw new \Exception('Cannot edit a posted or voided journal batch');
        }

        return DB::transaction(function () use ($batch, $data) {
            $lines = $data['lines'] ?? null;
            unset($data['lines']);

            $batch->update($data);

            if ($lines !== null) {
                // Delete existing lines
                $batch->lines()->delete();

                // Create new lines
                foreach ($lines as $index => $lineData) {
                    $lineData['batch_id'] = $batch->id;
                    $lineData['line_number'] = $lineData['line_number'] ?? ($index + 1);
                    JournalLine::create($lineData);
                }
            }

            $batch->recalculateTotals();

            return $batch->fresh('lines');
        });
    }

    public function post(JournalBatch $batch): JournalBatch
    {
        if (!$batch->canBePosted()) {
            throw new \Exception('Journal batch cannot be posted. It must be draft and balanced.');
        }

        $batch->post(auth()->id());

        return $batch->fresh();
    }

    public function void(JournalBatch $batch, ?string $reason = null): JournalBatch
    {
        if (!$batch->canBeVoided()) {
            throw new \Exception('Only posted journal batches can be voided');
        }

        $batch->void(auth()->id(), $reason);

        return $batch->fresh();
    }

    public function reverse(JournalBatch $batch, ?string $date = null): JournalBatch
    {
        if (!$batch->isPosted()) {
            throw new \Exception('Only posted journal batches can be reversed');
        }

        return DB::transaction(function () use ($batch, $date) {
            $reversalData = [
                'organization_id' => $batch->organization_id,
                'branch_id' => $batch->branch_id,
                'date' => $date ?? now()->toDateString(),
                'reference' => "Reversal of {$batch->number}",
                'description' => "Reversal of {$batch->description}",
                'type' => JournalBatch::TYPE_REVERSAL,
                'reversed_batch_id' => $batch->id,
                'currency_code' => $batch->currency_code,
                'exchange_rate' => $batch->exchange_rate,
            ];

            // Swap debits and credits
            $reversalLines = $batch->lines->map(function ($line) {
                return [
                    'account_id' => $line->account_id,
                    'cost_center_id' => $line->cost_center_id,
                    'description' => $line->description,
                    'debit' => $line->credit, // Swap
                    'credit' => $line->debit, // Swap
                    'party_id' => $line->party_id,
                    'reference' => $line->reference,
                ];
            })->toArray();

            $reversalData['lines'] = $reversalLines;

            $reversalBatch = $this->createBatch($reversalData);
            $this->post($reversalBatch);

            return $reversalBatch;
        });
    }

    public function createFromSource(Model $source, array $lines, ?string $description = null): JournalBatch
    {
        $data = [
            'organization_id' => $source->organization_id,
            'branch_id' => $source->branch_id ?? null,
            'date' => now()->toDateString(),
            'reference' => $source->number ?? "#{$source->id}",
            'description' => $description ?? class_basename($source) . " #{$source->id}",
            'type' => JournalBatch::TYPE_AUTO,
            'source_type' => get_class($source),
            'source_id' => $source->id,
            'lines' => $lines,
        ];

        $batch = $this->createBatch($data);
        $this->post($batch);

        return $batch;
    }

    public function getAccountStatement(
        int $accountId,
        ?string $from = null,
        ?string $to = null,
        ?int $costCenterId = null
    ): array {
        $query = JournalLine::byAccount($accountId)
            ->posted()
            ->with(['batch:id,number,date,description', 'costCenter:id,code,name'])
            ->orderBy('created_at');

        if ($costCenterId) {
            $query->byCostCenter($costCenterId);
        }

        if ($from || $to) {
            $query->whereHas('batch', function ($q) use ($from, $to) {
                if ($from) $q->where('date', '>=', $from);
                if ($to) $q->where('date', '<=', $to);
            });
        }

        $lines = $query->get();

        // Calculate opening balance
        $openingBalance = 0;
        if ($from) {
            $openingBalance = JournalLine::byAccount($accountId)
                ->posted()
                ->whereHas('batch', fn($q) => $q->where('date', '<', $from))
                ->when($costCenterId, fn($q) => $q->byCostCenter($costCenterId))
                ->selectRaw('SUM(debit) - SUM(credit) as balance')
                ->value('balance') ?? 0;
        }

        // Build statement
        $balance = $openingBalance;
        $statement = $lines->map(function ($line) use (&$balance) {
            $balance += $line->debit - $line->credit;
            return [
                'date' => $line->batch->date->format('Y-m-d'),
                'number' => $line->batch->number,
                'description' => $line->description ?? $line->batch->description,
                'debit' => $line->debit,
                'credit' => $line->credit,
                'balance' => $balance,
            ];
        });

        return [
            'opening_balance' => $openingBalance,
            'closing_balance' => $balance,
            'total_debit' => $lines->sum('debit'),
            'total_credit' => $lines->sum('credit'),
            'entries' => $statement,
        ];
    }

    public function getTrialBalance(int $organizationId, ?string $asOf = null): array
    {
        $date = $asOf ?? now()->toDateString();

        $balances = JournalLine::posted()
            ->whereHas('batch', function ($q) use ($organizationId, $date) {
                $q->where('organization_id', $organizationId)
                  ->where('date', '<=', $date);
            })
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->with('account:id,code,name,type')
            ->get()
            ->map(function ($item) {
                $balance = $item->total_debit - $item->total_credit;
                return [
                    'account_id' => $item->account_id,
                    'account_code' => $item->account->code,
                    'account_name' => $item->account->name,
                    'account_type' => $item->account->type,
                    'debit' => $balance > 0 ? $balance : 0,
                    'credit' => $balance < 0 ? abs($balance) : 0,
                ];
            })
            ->sortBy('account_code')
            ->values();

        return [
            'as_of' => $date,
            'total_debit' => $balances->sum('debit'),
            'total_credit' => $balances->sum('credit'),
            'accounts' => $balances,
        ];
    }

    public function getBatches(int $organizationId, array $filters = []): LengthAwarePaginator
    {
        $query = JournalBatch::where('organization_id', $organizationId)
            ->with(['lines.account:id,code,name', 'createdBy:id,name']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['from']) || isset($filters['to'])) {
            $query->betweenDates($filters['from'] ?? null, $filters['to'] ?? null);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }
}
