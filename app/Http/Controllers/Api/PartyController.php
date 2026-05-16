<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Party\AddPartyContactRequest;
use App\Http\Requests\Party\AddPartyRoleRequest;
use App\Http\Requests\Party\BulkActivatePartyRequest;
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

    public function index(int $organization): JsonResponse
    {
        $data = $this->partyService->getPaginated(
            perPage: request()->integer('per_page', 15),
            filters: ['organization_id' => $organization]
        );

        return $this->paginatedResponse($data);
    }

    public function store(StorePartyRequest $request, int $organization): JsonResponse
    {
        $party = $this->partyService->create(
            array_merge($request->validated(), ['organization_id' => $organization])
        );

        return $this->createdResponse(
            new PartyResource($party)
        );
    }

    public function show(int $organization, int $party): JsonResponse
    {
        $party = $this->partyService->findById($party);

        if (! $party || $party->organization_id !== $organization) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PartyResource($party)
        );
    }

    public function update(UpdatePartyRequest $request, int $organization, int $party): JsonResponse
    {
        $existingParty = $this->partyService->findById($party);

        if (! $existingParty || $existingParty->organization_id !== $organization) {
            return $this->notFoundResponse();
        }

        $this->partyService->update($party, $request->validated());
        $party = $this->partyService->findById($party);

        return $this->successResponse(
            new PartyResource($party),
            'Resource updated successfully'
        );
    }

    public function destroy(int $organization, int $party): JsonResponse
    {
        $existingParty = $this->partyService->findById($party);

        if (! $existingParty || $existingParty->organization_id !== $organization) {
            return $this->notFoundResponse();
        }

        $this->partyService->delete($party);

        return $this->noContentResponse();
    }

    public function addContact(AddPartyContactRequest $request, int $organization, int $party): JsonResponse
    {
        $existingParty = $this->partyService->findById($party);

        if (! $existingParty || $existingParty->organization_id !== $organization) {
            return $this->notFoundResponse();
        }

        $contact = $this->partyService->addContact($party, $request->validated());

        return $this->createdResponse($contact, 'Contact added successfully');
    }

    public function addRole(AddPartyRoleRequest $request, int $organization, int $party): JsonResponse
    {
        $existingParty = $this->partyService->findById($party);

        if (! $existingParty || $existingParty->organization_id !== $organization) {
            return $this->notFoundResponse();
        }

        $this->partyService->addRole($party, $request->validated()['role']);

        return $this->createdResponse(null, 'Role added successfully');
    }

    public function statement(int $organization, int $party): JsonResponse
    {
        $party = $this->partyService->findById($party);

        if (! $party || $party->organization_id !== $organization) {
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

    public function statistics(int $organization): JsonResponse
    {
        $stats = $this->partyService->getStatistics($organization);

        return $this->successResponse($stats, 'Party statistics retrieved');
    }

    public function search(int $organization): JsonResponse
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

        $criteria['organization_id'] = $organization;
        $perPage = request()->integer('per_page', 15);
        $results = $this->partyService->search($criteria, $perPage);

        return $this->paginatedResponse($results);
    }

    public function bulkActivate(BulkActivatePartyRequest $request, int $organization): JsonResponse
    {
        $validated = $request->validated();

        // Verify all parties belong to the organization
        $parties = $this->partyService->findByIds($validated['party_ids']);
        $invalidParties = $parties->where('organization_id', '!=', $organization);

        if ($invalidParties->isNotEmpty()) {
            return $this->errorResponse('Some parties do not belong to this organization', 403);
        }

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

    public function export(int $organization): mixed
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        // $parties = $this->partyService->findByOrganization($organization);
        // return Excel::download(new PartiesExport($parties), 'parties.xlsx');

        return $this->errorResponse('Export functionality not implemented yet', 501);
    }

    public function toggleStatus(int $organization, int $party): JsonResponse
    {
        $existingParty = $this->partyService->findById($party);

        if (! $existingParty || $existingParty->organization_id !== $organization) {
            return $this->notFoundResponse();
        }

        $party = $this->partyService->toggleStatus($party);

        if (! $party) {
            return $this->notFoundResponse();
        }

        $message = $party->is_active ? 'Party activated successfully' : 'Party deactivated successfully';

        return $this->successResponse(new PartyResource($party), $message);
    }
}
