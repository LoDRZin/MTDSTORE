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
            "external_reference" => $order->uuid, // ← CRÍTICO: vincula o webhook ao pedido
            "payer" => [
                "email" => $order->customer->email ?? "cliente@example.com",
            ]
        ]);

        // Salva a referência externa no pedido para rastreio
        $order->update(['external_reference' => (string) $payment->id]);

        return new PaymentIntentDTO(
            externalReference: (string) $payment->id,
            checkoutUrl: $payment->point_of_interaction->transaction_data->ticket_url ?? null,
            qrCode: $payment->point_of_interaction->transaction_data->qr_code ?? null,
            gatewayName: 'mercadopago'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secretToken = config('services.mercadopago.webhook_secret');

        // Se não há secret configurado, aceita (desenvolvimento)
        if (!$secretToken) {
            return true;
        }

        if (!$request->hasHeader('x-signature')) {
            return false;
        }

        // Verificação de assinatura HMAC do MercadoPago
        $xSignature = $request->header('x-signature');
        $xRequestId = $request->header('x-request-id', '');
        $queryParams = $request->query();
        $dataId = $queryParams['data.id'] ?? ($request->input('data.id') ?? '');

        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:" . now()->timestamp . ";";

        // Parse do x-signature header: ts=...,v1=...
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $val] = explode('=', trim($part), 2);
            $parts[$key] = $val;
        }

        $ts = $parts['ts'] ?? '';
        $v1 = $parts['v1'] ?? '';
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
        $expectedSignature = hash_hmac('sha256', $manifest, $secretToken);

        return hash_equals($expectedSignature, $v1);
    }

    public function handleWebhook(Request $request): WebhookEventDTO
    {
        $payload = json_decode($request->getContent(), true);
        $type = $payload['type'] ?? $payload['action'] ?? '';
        $dataId = $payload['data']['id'] ?? null;

        $status = 'pending';
        $orderUuid = '';

        // MercadoPago envia o ID do pagamento; precisamos buscar para pegar o external_reference
        if ($dataId && str_contains($type, 'payment')) {
            try {
                $client = new PaymentClient();
                $payment = $client->get((int) $dataId);

                $mpStatus = $payment->status ?? 'pending';
                if ($mpStatus === 'approved') {
                    $status = 'paid';
                } elseif (in_array($mpStatus, ['rejected', 'cancelled'])) {
                    $status = 'failed';
                } elseif ($mpStatus === 'refunded') {
                    $status = 'refunded';
                } elseif ($mpStatus === 'charged_back') {
                    $status = 'chargeback';
                }

                // external_reference é o UUID do nosso pedido
                $orderUuid = $payment->external_reference ?? '';
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('MercadoPago webhook fetch failed: ' . $e->getMessage());
            }
        }

        return new WebhookEventDTO(
            gatewayName: 'mercadopago',
            externalEventId: (string) ($payload['id'] ?? uniqid()),
            orderUuid: $orderUuid,
            status: $status
        );
    }

    public function refund(Order $order): bool
    {
        // Not fully implemented, placeholder
        return true;
    }
}

