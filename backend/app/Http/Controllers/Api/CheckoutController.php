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
            'email'               => 'required|email',
            'items'               => 'required|array',
            'items.*.product_id'  => 'required|integer',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.variant_id'  => 'nullable|integer',
            'coupon_code'         => 'nullable|string',
            'gateway'             => 'required|string|in:mercadopago,stripe,efi,oxapay,wise',
        ]);

        try {
            // 1. Encontra ou cria o usuário pelo e-mail
            $user = \App\Models\User::firstOrCreate(
                ['email' => $request->email],
                [
                    'name'     => 'Cliente',
                    'password' => bcrypt(\Illuminate\Support\Str::random(24)),
                ]
            );

            // 2. Cria o pedido no banco (status: pending)
            $order = $this->checkoutService->process(
                $user,
                $request->items,
                $request->coupon_code,
                $request->gateway
            );

            // 3. Dispara o gateway de pagamento para criar a cobrança real
            $gateway = \App\Gateways\PaymentGatewayFactory::make($request->gateway);
            $paymentIntent = $gateway->createCharge($order->fresh(['customer']));

            return response()->json([
                'message'             => 'Pedido criado com sucesso',
                'order'               => ['uuid' => $order->uuid],
                'payment' => [
                    'gateway'            => $request->gateway,
                    'external_reference' => $paymentIntent->externalReference,
                    'checkout_url'       => $paymentIntent->checkoutUrl,
                    'qr_code'            => $paymentIntent->qrCode,
                ],
            ], 201);

        } catch (\Exception $e) {
            $isDatabaseError = $e instanceof \Illuminate\Database\QueryException;
            
            if ($isDatabaseError) {
                \Illuminate\Support\Facades\Log::error("Database Error on Checkout: " . $e->getMessage());
            }

            return response()->json([
                'error' => [
                    'code'     => $isDatabaseError ? 'INTERNAL_SERVER_ERROR' : 'UNPROCESSABLE_ENTITY',
                    'message'  => $isDatabaseError ? 'Ocorreu um erro interno ao processar seu pedido. Tente novamente mais tarde.' : $e->getMessage(),
                    'trace_id' => request()->header('X-Correlation-ID', uniqid()),
                    'details'  => [],
                ],
            ], $isDatabaseError ? 500 : 422);
        }
    }
}

