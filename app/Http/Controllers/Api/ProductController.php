<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends BaseApiController
{
    public function __construct(
        protected ProductService $productService
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->productService->getPaginated(
            perPage: request()->integer('per_page', 15)
        );

        return $this->paginatedResponse($data);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return $this->createdResponse(
            new ProductResource($product)
        );
    }

    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (!$product) {
            return $this->notFoundResponse();
        }

        return $this->successResponse(
            new ProductResource($product)
        );
    }

    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        if (!$this->productService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->productService->update($id, $request->validated());
        $product = $this->productService->findById($id);

        return $this->successResponse(
            new ProductResource($product),
            'Resource updated successfully'
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->productService->exists($id)) {
            return $this->notFoundResponse();
        }

        $this->productService->delete($id);

        return $this->noContentResponse();
    }

    public function import(): JsonResponse
    {
        // TODO: Validate Excel file upload
        // TODO: Implement ImportProductsAction
        // $file = request()->file('file');
        // $result = app(ImportProductsAction::class)->execute($file);

        return $this->successResponse(
            ['message' => 'Import functionality not implemented yet'],
            'Products import queued'
        );
    }

    public function lowStock(): JsonResponse
    {
        $threshold = request()->integer('threshold', 10);
        $products = $this->productService->getLowStock($threshold);

        return $this->successResponse($products, 'Low stock products retrieved');
    }

    public function statistics(): JsonResponse
    {
        $stats = $this->productService->getStatistics();

        return $this->successResponse($stats, 'Product statistics retrieved');
    }

    public function search(): JsonResponse
    {
        $criteria = request()->only([
            'sku_like',
            'name_like',
            'description_like',
            'type',
            'category_id',
            'is_active',
            'track_inventory',
            'cost_price_from',
            'cost_price_to',
            'selling_price_from',
            'selling_price_to',
        ]);

        $perPage = request()->integer('per_page', 15);
        $results = $this->productService->search($criteria, $perPage);

        return $this->paginatedResponse($results);
    }

    public function bulkUpdatePrices(): JsonResponse
    {
        $validated = request()->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',
            'cost_price' => 'sometimes|numeric|min:0',
            'selling_price' => 'sometimes|numeric|min:0',
            'cost_price_percentage' => 'sometimes|numeric',
            'selling_price_percentage' => 'sometimes|numeric',
        ]);

        $productIds = $validated['product_ids'];
        unset($validated['product_ids']);

        $count = $this->productService->bulkUpdatePrices($productIds, $validated);

        return $this->successResponse(
            ['updated_count' => $count],
            "Successfully updated prices for {$count} products"
        );
    }

    public function bulkActivate(): JsonResponse
    {
        $validated = request()->validate([
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|integer|exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        $count = $this->productService->updateMany(
            $validated['product_ids'],
            ['is_active' => $validated['is_active']]
        );

        $status = $validated['is_active'] ? 'activated' : 'deactivated';
        return $this->successResponse(
            ['updated_count' => $count],
            "Successfully {$status} {$count} products"
        );
    }

    public function export(): mixed
    {
        // TODO: Implement Excel export using Maatwebsite\Excel
        // $products = $this->productService->getAll();
        // return Excel::download(new ProductsExport($products), 'products.xlsx');

        return $this->errorResponse('Export functionality not implemented yet', 501);
    }
}
