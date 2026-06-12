<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\CostCenterResource;
use App\Models\CostCenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CostCenterController extends BaseApiController
{
    public function index(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;
        $type = request()->input('type');
        $parentId = request()->input('parent_id');
        $activeOnly = request()->boolean('active_only', true);

        $query = CostCenter::where('organization_id', $organizationId);

        if ($activeOnly) {
            $query->active();
        }

        if ($type) {
            $query->byType($type);
        }

        if ($parentId === 'root') {
            $query->root();
        } elseif ($parentId) {
            $query->where('parent_id', $parentId);
        }

        $costCenters = $query->with('children')
            ->orderBy('code')
            ->get();

        return $this->successResponse(CostCenterResource::collection($costCenters));
    }

    public function tree(): JsonResponse
    {
        $organizationId = Auth::user()->organization_id;

        $costCenters = CostCenter::where('organization_id', $organizationId)
            ->root()
            ->active()
            ->with(['children' => function ($query) {
                $query->active()->with(['children' => function ($q) {
                    $q->active();
                }]);
            }])
            ->orderBy('code')
            ->get();

        return $this->successResponse(CostCenterResource::collection($costCenters));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:cost_centers,id',
            'code' => 'required|string|max:50|unique:cost_centers,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:branch,department,project,other',
            'is_active' => 'boolean',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $validated['organization_id'] = Auth::user()->organization_id;

        // Calculate level and path
        if ($validated['parent_id']) {
            $parent = CostCenter::find($validated['parent_id']);
            $validated['level'] = $parent->level + 1;
            $validated['path'] = $parent->path . '/' . $validated['code'];
        } else {
            $validated['level'] = 0;
            $validated['path'] = $validated['code'];
        }

        $costCenter = CostCenter::create($validated);

        return $this->createdResponse(
            new CostCenterResource($costCenter),
            'Cost center created successfully'
        );
    }

    public function show(int $id): JsonResponse
    {
        $costCenter = CostCenter::with(['parent', 'children'])->find($id);

        if (!$costCenter) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(new CostCenterResource($costCenter));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $costCenter = CostCenter::find($id);

        if (!$costCenter) {
            return $this->notFoundResponse();
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:branch,department,project,other',
            'is_active' => 'boolean',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $costCenter->update($validated);

        return $this->successResponse(
            new CostCenterResource($costCenter),
            'Cost center updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        $costCenter = CostCenter::find($id);

        if (!$costCenter) {
            return $this->notFoundResponse();
        }

        if ($costCenter->hasChildren()) {
            return $this->errorResponse('Cannot delete cost center with children', 422);
        }

        // Check if used in journal lines
        if ($costCenter->journalLines()->exists()) {
            return $this->errorResponse('Cannot delete cost center with journal entries', 422);
        }

        $costCenter->delete();

        return $this->noContentResponse();
    }

    public function statistics(int $id): JsonResponse
    {
        $costCenter = CostCenter::find($id);

        if (!$costCenter) {
            return $this->notFoundResponse();
        }

        $from = request()->input('from');
        $to = request()->input('to');

        return $this->successResponse([
            'budget' => $costCenter->budget,
            'total_budget' => $costCenter->getTotalBudget(),
            'actual_spending' => $costCenter->getActualSpending($from, $to),
            'children_count' => $costCenter->children()->count(),
        ]);
    }
}
