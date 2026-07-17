<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * GET /api/products
     *
     * Accepted query params:
     *   - search      string   Full-text name search
     *   - category    string   Category slug (includes child categories)
     *   - min_price   numeric  Minimum price filter
     *   - max_price   numeric  Maximum price filter
     *   - in_stock    boolean  If "1", only products with available stock
     *   - per_page    int      Items per page (default 12, max 48)
     */
    public function index(Request $request)
    {
        $request->validate([
            'search'    => ['sometimes', 'string', 'max:100'],
            'category'  => ['sometimes', 'string', 'max:100'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
            'max_price' => ['sometimes', 'numeric', 'min:0'],
            'in_stock'  => ['sometimes', 'boolean'],
            'per_page'  => ['sometimes', 'integer', 'min:1', 'max:48'],
        ]);

        $perPage = min((int) $request->input('per_page', 12), 48);

        $query = Product::active()->with(['categories:id,name,slug', 'variants']);

        // Full-text search on name
        if ($search = $request->input('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        // Filter by category slug
        if ($category = $request->input('category')) {
            $query->byCategory($category);
        }

        // Price range filters
        if ($minPrice = $request->input('min_price')) {
            $query->minPrice((float) $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->maxPrice((float) $maxPrice);
        }

        // In-stock filter
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        $products = $query->latest()->paginate($perPage);

        return ProductResource::collection($products);
    }

    public function show(string $slug)
    {
        $product = Product::active()
            ->with(['categories:id,name,slug', 'variants'])
            ->where('slug', $slug)
            ->firstOrFail();

        return new ProductResource($product);
    }
}
