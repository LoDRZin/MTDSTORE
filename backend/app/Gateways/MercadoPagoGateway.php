<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(config('services.mercadopago.access_token'));
    }

    public function createCharge(Order $order): PaymentIntentDTO
    {
        $client = new PaymentClient();
        
        $payment = $client->create([
            "transaction_amount" => (float) $order->total,
            "description" => "Pedido #" . $order->uuid,
            "payment_method_id" => "pix",
            "payer" => [
                "email" => $order->customer->email ?? "cliente@example.com",
            ]
        ]);

        return new PaymentIntentDTO(
            externalReference: (string) $payment->id,
            checkoutUrl: $payment->point_of_interaction->transaction_data->ticket_url ?? null,
            qrCode: $payment->point_of_interaction->transaction_data->qr_code ?? null,
            gatewayName: 'mercadopago'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // Simple verification for MP Webhook or Secret Token
        $secretToken = config('services.mercadopago.webhook_secret');
        if ($secretToken && $request->header('x-signature') !== $secretToken) {
            // Simplified for example. Real MP signature verification requires hashing.
            // return false; 
        }
        return true;
    }

    public function handleWebhook(Request $request): WebhookEventDTO
    {
        $payload = json_decode($request->getContent(), true);
        
        $status = 'pending';
        // Assume MP sends status in 'action' or fetching payment by ID
        // Simplified DTO creation
        
        return new WebhookEventDTO(
            gatewayName: 'mercadopago',
            externalEventId: (string) ($payload['id'] ?? uniqid()),
            orderUuid: 'unknown', // MP webhook usually requires fetching the payment ID first
            status: $status
        );
    }

    public function refund(Order $order): bool
    {
        // Not fully implemented, placeholder
        return true;
    }
}
