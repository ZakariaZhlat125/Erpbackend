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

        if (!$party) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new PartyResource($party)
        );
    }

    public function update(UpdatePartyRequest $request, int $id): JsonResponse
    {
        if (!$this->partyService->exists($id)) {
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
        if (!$this->partyService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->partyService->delete($id);

        return $this->noContentResponse();
    }
}
