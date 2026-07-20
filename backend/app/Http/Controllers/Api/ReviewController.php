<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\ReviewSetting;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function show(string $order, int $product)
    {
        [$order, $product, $settings] = $this->resolveReviewable($order, $product);

        return response()->json([
            'order' => $order->uuid,
            'product' => ['id' => $product->id, 'name' => $product->name],
            'already_reviewed' => Review::where('order_id', $order->id)->where('product_id', $product->id)->exists(),
            'suggested_phrases' => $settings->suggested_phrases ?? [],
        ]);
    }

    public function store(Request $request, string $order, int $product)
    {
        [$order, $product, $settings] = $this->resolveReviewable($order, $product);

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'customer_id' => $order->customer_id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => $settings->auto_publish ? 'published' : 'private',
        ]);

        return response()->json(['id' => $review->id, 'status' => $review->status], 201);
    }

    private function resolveReviewable(string $orderUuid, int $productId): array
    {
        $settings = ReviewSetting::current();
        abort_unless($settings->enabled, 404);

        $order = Order::where('uuid', $orderUuid)->where('status', 'paid')->firstOrFail();
        abort_unless($order->items()->where('product_id', $productId)->exists(), 404);

        return [$order, Product::findOrFail($productId), $settings];
    }
}
