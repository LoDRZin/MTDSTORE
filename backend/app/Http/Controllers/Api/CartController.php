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
            'coupon_code' => 'nullable|string',
            'payment_method' => 'nullable|string|in:mercadopago,stripe,efi,oxapay,wise',
        ]);

        $dto = $this->cartService->calculateTotal(
            $request->items,
            $request->coupon_code,
            $request->user('sanctum')?->id,
            $request->input('payment_method'),
        );

        return response()->json([
            'subtotal' => $dto->subtotal,
            'discount' => $dto->discount,
            'total' => $dto->total,
            'coupon_code' => $dto->couponCode,
            'errors' => $dto->errors
        ]);
    }}
