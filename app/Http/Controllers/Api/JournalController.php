<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\JournalBatchResource;
use App\Models\JournalBatch;
use App\Services\JournalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JournalController extends BaseApiController
{
    public function __construct(
        protected JournalService $journalService
    ) {}

    public function index(): JsonResponse
    {
        $filters = request()->only(['status', 'type', 'from', 'to', 'search', 'per_page']);
        
        $batches = $this->journalService->getBatches(
            Auth::user()->organization_id,
            $filters
        );

        return $this->paginatedResponse($batches, JournalBatchResource::class);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'nullable|in:manual,adjustment,closing,opening',
            'currency_code' => 'nullable|string|size:3',
            'exchange_rate' => 'nullable|numeric|min:0',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.party_id' => 'nullable|exists:parties,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.reference' => 'nullable|string',
            'lines.*.due_date' => 'nullable|date',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;
        $validated['type'] = $validated['type'] ?? 'manual';

        try {
            $batch = $this->journalService->createBatch($validated);

            return $this->createdResponse(
                new JournalBatchResource($batch),
                'Journal batch created successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show(int $id): JsonResponse
    {
        $batch = JournalBatch::with([
            'lines.account:id,code,name',
            'lines.costCenter:id,code,name',
            'lines.party:id,name',
            'createdBy:id,name',
            'postedBy:id,name',
        ])->find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new JournalBatchResource($batch));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $batch = JournalBatch::find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        if (!$batch->canBeEdited()) {
            return $this->errorResponse('Cannot edit a posted or voided journal batch', 422);
        }

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'lines' => 'sometimes|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
            'lines.*.description' => 'nullable|string',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.party_id' => 'nullable|exists:parties,id',
        ]);

        try {
            $batch = $this->journalService->updateBatch($batch, $validated);

            return $this->successResponse(
                new JournalBatchResource($batch),
                'Journal batch updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $batch = JournalBatch::find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        if (!$batch->isDraft()) {
            return $this->errorResponse('Only draft journal batches can be deleted', 422);
        }

        $batch->delete();

        return $this->noContentResponse();
    }

    public function post(int $id): JsonResponse
    {
        $batch = JournalBatch::find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        try {
            $batch = $this->journalService->post($batch);

            return $this->successResponse(
                new JournalBatchResource($batch),
                'Journal batch posted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function void(Request $request, int $id): JsonResponse
    {
        $batch = JournalBatch::find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        $reason = $request->input('reason');

        try {
            $batch = $this->journalService->void($batch, $reason);

            return $this->successResponse(
                new JournalBatchResource($batch),
                'Journal batch voided successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function reverse(Request $request, int $id): JsonResponse
    {
        $batch = JournalBatch::find($id);

        if (!$batch) {
            return $this->notFoundResponse();
        }

        $date = $request->input('date');

        try {
            $reversalBatch = $this->journalService->reverse($batch, $date);

            return $this->createdResponse(
                new JournalBatchResource($reversalBatch),
                'Journal batch reversed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function accountStatement(Request $request, int $accountId): JsonResponse
    {
        $validated = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
        ]);

        $statement = $this->journalService->getAccountStatement(
            $accountId,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
            $validated['cost_center_id'] ?? null
        );

        return $this->successResponse($statement);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $asOf = $request->input('as_of');

        $trialBalance = $this->journalService->getTrialBalance(
            Auth::user()->organization_id,
            $asOf
        );

        return $this->successResponse($trialBalance);
    }
}
