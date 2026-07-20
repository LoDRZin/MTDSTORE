<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected \App\Services\CartService $cartService) {}

    public function calculate(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable|integer',
            'coupon_code' => 'nullable|string'
        ]);

        $dto = $this->cartService->calculateTotal($request->items, $request->coupon_code);

        return response()->json([
            'subtotal' => $dto->subtotal,
            'discount' => $dto->discount,
            'total' => $dto->total,
            'coupon_code' => $dto->couponCode,
            'errors' => $dto->errors
        ]);
    }}
