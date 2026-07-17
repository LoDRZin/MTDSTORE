<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
    /**
     * Return the full active category tree (roots + children recursively).
     * Cached for 1 hour and invalidated when categories change.
     */
    public function index(): JsonResponse
    {
        $categories = Cache::remember(
            'categories.tree',
            now()->addHour(),
            fn () => Category::active()
                ->roots()
                ->with('activeChildren.activeChildren') // 3 levels deep
                ->orderBy('order')
                ->get()
        );

        return CategoryResource::collection($categories)->response();
    }
}
