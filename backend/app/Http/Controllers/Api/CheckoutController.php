<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(protected \App\Services\CheckoutService $checkoutService) {}

    public function process(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'items' => 'required|array',
            'items.*.product_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'coupon_code' => 'nullable|string',
            'gateway' => 'required|string|in:stripe,paypal,mercadopago' // Example gateways
        ]);

        try {
            // Find or create guest user by email
            $user = \App\Models\User::firstOrCreate(
                ['email' => $request->email],
                [
                    'name' => 'Guest Customer',
                    'password' => bcrypt(\Illuminate\Support\Str::random(24))
                ]
            );

            $order = $this->checkoutService->process(
                $user,
                $request->items,
                $request->coupon_code,
                $request->gateway
            );

            return response()->json([
                'message' => 'Pedido criado com sucesso',
                'order' => $order
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => [
                    'code' => 'UNPROCESSABLE_ENTITY',
                    'message' => $e->getMessage(),
                    'trace_id' => request()->header('X-Correlation-ID', uniqid()),
                    'details' => []
                ]
            ], 422);
        }
    }}
