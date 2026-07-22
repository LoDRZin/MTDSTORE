<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCharge(Order $order): PaymentIntentDTO
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'https://mtdstore.xyz'));
        
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card', 'pix'], // Permite cartão e PIX no Stripe
            'line_items' => [[
                'price_data' => [
                    'currency' => 'brl',
                    'product_data' => [
                        'name' => 'Pedido #' . explode('-', $order->uuid)[0],
                    ],
                    'unit_amount' => (int) ($order->total * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => rtrim($frontendUrl, '/') . '/pedido/' . $order->uuid . '/sucesso',
            'cancel_url' => rtrim($frontendUrl, '/') . '/checkout',
            'client_reference_id' => $order->uuid,
            'customer_email' => $order->customer->email ?? null,
            'metadata' => ['order_uuid' => $order->uuid],
        ]);

        $order->update(['external_reference' => $session->id]);

        return new PaymentIntentDTO(
            externalReference: $session->id,
            checkoutUrl: $session->url,
            qrCode: null,
            gatewayName: 'stripe'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        try {
            Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function handleWebhook(Request $request): WebhookEventDTO
    {
        $payload = json_decode($request->getContent(), true);
        $type = $payload['type'] ?? '';
        $intent = $payload['data']['object'] ?? [];

        $status = 'pending';
        $orderUuid = '';

        if ($type === 'checkout.session.completed') {
            $status = 'paid';
            $orderUuid = $intent['metadata']['order_uuid'] ?? $intent['client_reference_id'] ?? '';
        } elseif ($type === 'checkout.session.expired') {
            $status = 'failed';
            $orderUuid = $intent['metadata']['order_uuid'] ?? $intent['client_reference_id'] ?? '';
        } elseif ($type === 'payment_intent.succeeded') {
            // Em caso do webhook alternativo ser disparado
            $status = 'paid';
            $orderUuid = $intent['metadata']['order_uuid'] ?? '';
        } elseif ($type === 'charge.refunded') {
            $status = 'refunded';
            // Para charge, o uuid geralmente está no payment_intent (precisamos buscar o order)
            // ou metadata se foi copiado. Stripe transfere metadata da session para intent/charge
            $orderUuid = $intent['metadata']['order_uuid'] ?? '';
        } elseif ($type === 'charge.dispute.created') {
            $status = 'chargeback';
            $orderUuid = $intent['metadata']['order_uuid'] ?? '';
        }

        return new WebhookEventDTO(
            gatewayName: 'stripe',
            externalEventId: $payload['id'],
            orderUuid: $orderUuid,
            status: $status
        );
    }

    public function refund(Order $order): bool
    {
        try {
            \Stripe\Refund::create([
                'payment_intent' => $order->external_reference,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
