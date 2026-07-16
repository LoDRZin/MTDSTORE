<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;

class StripeGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCharge(Order $order): PaymentIntentDTO
    {
        $intent = PaymentIntent::create([
            'amount' => (int) ($order->total * 100), // Stripe uses cents
            'currency' => 'brl',
            'metadata' => ['order_uuid' => $order->uuid],
        ]);

        return new PaymentIntentDTO(
            externalReference: $intent->id,
            checkoutUrl: $intent->client_secret, // Used for frontend Stripe Elements
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
        if ($type === 'payment_intent.succeeded') {
            $status = 'paid';
        } elseif ($type === 'payment_intent.payment_failed') {
            $status = 'failed';
        }

        return new WebhookEventDTO(
            gatewayName: 'stripe',
            externalEventId: $payload['id'],
            orderUuid: $intent['metadata']['order_uuid'] ?? '',
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
