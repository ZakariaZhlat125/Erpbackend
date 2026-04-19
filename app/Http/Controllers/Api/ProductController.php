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
}
