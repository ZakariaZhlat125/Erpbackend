<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Party\StorePartyRequest;
use App\Http\Requests\Party\UpdatePartyRequest;
use App\Http\Resources\PartyResource;
use App\Services\PartyService;
use Illuminate\Http\JsonResponse;

class PartyController extends BaseApiController
{
    public function __construct(
        protected PartyService $partyService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->partyService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StorePartyRequest $request): JsonResponse
    {
        $party = $this->partyService->create($request->validated());

        return $this->createdResponse(
            new PartyResource($party)
        );
    }

    public function show(int $id): JsonResponse
    {
        $party = $this->partyService->findById($id);

        if (! $party) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PartyResource($party)
        );
    }

    public function update(UpdatePartyRequest $request, int $id): JsonResponse
    {
        if (! $this->partyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->partyService->update($id, $request->validated());
        $party = $this->partyService->findById($id);

        return $this->successResponse(
            new PartyResource($party),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (! $this->partyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->partyService->delete($id);

        return $this->noContentResponse();
    }

    public function addContact(int $id): JsonResponse
    {
        if (! $this->partyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $contactData = request()->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $contact = $this->partyService->addContact($id, $contactData);

        return $this->createdResponse($contact, 'Contact added successfully');
    }

    public function addRole(int $id): JsonResponse
    {
        if (! $this->partyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $validated = request()->validate([
            'role' => ['required', 'string', 'in:customer,supplier,agent,contractor'],
        ]);

        $this->partyService->addRole($id, $validated['role']);

        return $this->createdResponse(null, 'Role added successfully');
    }

    public function statement(int $id): JsonResponse
    {
        $party = $this->partyService->findById($id);

        if (! $party) {
            return $this->notFoundResponse();
        }

        // TODO: Get date range from request
        // TODO: Implement GetPartyStatementQuery
        // $statement = app(GetPartyStatementQuery::class)->execute($party, $dateFrom, $dateTo);

        return $this->successResponse([
            'party' => new PartyResource($party),
            'invoices' => [],
            'payments' => [],
            'balance' => 0,
        ]);
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->partyService->getStatistics();

        return $this->successResponse($stats, 'Party statistics retrieved');
    }

    public function search(): JsonResponse
    {
        $criteria = request()->only([
            'code_like',
            'display_name_like',
            'legal_name_like',
            'tax_number',
            'type',
            'is_active',
            'default_currency',
        ]);

        $perPage = request()->integer('per_page', 15);
        $results = $this->partyService->search($criteria, $perPage);

        return $this->paginatedResponse($results);
    }

    public function bulkActivate(): JsonResponse
    {
        $validated = request()->validate([
            'party_ids' => 'required|array|min:1',
            'party_ids.*' => 'required|integer|exists:parties,id',
            'is_active' => 'required|boolean',
        ]);

        $count = $this->partyService->updateMany(
            $validated['party_ids'],
            ['is_active' => $validated['is_active']]
        );

        $status = $validated['is_active'] ? 'activated' : 'deactivated';

        return $this->successResponse(
            ['updated_count' => $count],
            "Successfully {$status} {$count} parties"
        );
    }

    public function export(): mixed
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        // $parties = $this->partyService->getAll();
        // return Excel::download(new PartiesExport($parties), 'parties.xlsx');

        return $this->errorResponse('Export functionality not implemented yet', 501);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $party = $this->partyService->toggleStatus($id);

        if (! $party) {
            return $this->notFoundResponse();
        }

        $message = $party->is_active ? 'Party activated successfully' : 'Party deactivated successfully';

        return $this->successResponse(new PartyResource($party), $message);
    }
}
