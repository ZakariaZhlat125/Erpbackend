<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ProductService extends BaseService
{
    public function __construct(ProductRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function getLowStock(int $threshold = 10)
    {
        return Product::with(['stockBalances', 'category', 'unit'])
            ->whereHas('stockBalances', function ($query) use ($threshold) {
                $query->havingRaw('SUM(quantity_on_hand) <= ?', [$threshold]);
            })
            ->where('track_inventory', true)
            ->where('is_active', true)
            ->get()
            ->map(function ($product) {
                $product->total_stock = $product->stockBalances->sum('quantity_on_hand');
                return $product;
            });
    }

    public function getStatistics(): array
    {
        $query = Product::query();

        $stats = [
            'total_count' => (clone $query)->count(),
            'active_count' => (clone $query)->where('is_active', true)->count(),
            'inactive_count' => (clone $query)->where('is_active', false)->count(),
            'products_count' => (clone $query)->where('type', 'product')->count(),
            'services_count' => (clone $query)->where('type', 'service')->count(),
            'tracked_count' => (clone $query)->where('track_inventory', true)->count(),
            'avg_cost_price' => (clone $query)->avg('cost_price'),
            'avg_selling_price' => (clone $query)->avg('selling_price'),
            'total_inventory_value' => 0,
        ];

        $inventoryValue = DB::table('products')
            ->join('stock_balances', 'products.id', '=', 'stock_balances.product_id')
            ->select(DB::raw('SUM(products.cost_price * stock_balances.quantity_on_hand) as total_value'))
            ->where('products.track_inventory', true)
            ->first();

        $stats['total_inventory_value'] = $inventoryValue->total_value ?? 0;

        $byCategory = Product::select('category_id', DB::raw('COUNT(*) as count'))
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->with('category:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category?->name ?? 'Unknown',
                    'count' => $item->count,
                ];
            });

        $stats['by_category'] = $byCategory;

        return $stats;
    }

    public function bulkUpdatePrices(array $productIds, array $priceData): int
    {
        $updateData = [];

        if (isset($priceData['cost_price_percentage'])) {
            $products = $this->findByIds($productIds);
            foreach ($products as $product) {
                $newCostPrice = $product->cost_price * (1 + ($priceData['cost_price_percentage'] / 100));
                $product->update(['cost_price' => $newCostPrice]);
            }
            return count($products);
        }

        if (isset($priceData['selling_price_percentage'])) {
            $products = $this->findByIds($productIds);
            foreach ($products as $product) {
                $newSellingPrice = $product->selling_price * (1 + ($priceData['selling_price_percentage'] / 100));
                $product->update(['selling_price' => $newSellingPrice]);
            }
            return count($products);
        }

        if (isset($priceData['cost_price'])) {
            $updateData['cost_price'] = $priceData['cost_price'];
        }

        if (isset($priceData['selling_price'])) {
            $updateData['selling_price'] = $priceData['selling_price'];
        }

        if (!empty($updateData)) {
            return $this->updateMany($productIds, $updateData);
        }

        return 0;
    }
}
