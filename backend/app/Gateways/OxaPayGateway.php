<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OxaPay Gateway — Pagamentos em Criptomoeda
 *
 * Fluxo:
 *  1. createCharge() → chama /merchants/request/whiteLabel, recebe payLink
 *  2. Frontend redireciona o usuário para o payLink
 *  3. OxaPay envia webhook POST para /api/v1/webhooks/oxapay quando confirmado
 *  4. handleWebhook() lê o status e dispara ProcessPaymentWebhook
 *
 * Para ativar:
 *  - Crie uma conta em https://oxapay.com
 *  - Gere uma Merchant API Key
 *  - Configure OXAPAY_MERCHANT_KEY no .env
 *  - Configure OXAPAY_WEBHOOK_SECRET no .env (se usar assinatura HMAC)
 *  - Defina a URL de callback no painel da OxaPay: https://mtdstore.onrender.com/api/v1/webhooks/oxapay
 */
class OxaPayGateway implements PaymentGatewayInterface
{
    protected string $merchantKey;
    protected string $baseUrl;
    protected string $webhookSecret;

    public function __construct()
    {
        $this->merchantKey   = config('services.oxapay.merchant_key', '');
        $this->baseUrl       = config('services.oxapay.base_url', 'https://api.oxapay.com');
        $this->webhookSecret = config('services.oxapay.webhook_secret', '');
    }

    public function createCharge(Order $order): PaymentIntentDTO
    {
        $frontendUrl = config('app.frontend_url', 'https://mtdstore.xyz');
        $successUrl  = rtrim($frontendUrl, '/') . '/pedido/' . $order->uuid . '/sucesso';
        $cancelUrl   = rtrim($frontendUrl, '/') . '/checkout';

        $response = Http::post("{$this->baseUrl}/merchants/request/whiteLabel", [
            'merchant'       => $this->merchantKey,
            'amount'         => (float) $order->total,
            'currency'       => 'USD',           // OxaPay converte automaticamente
            'lifeTime'       => 30,              // Minutos para pagar
            'callbackUrl'    => route('api.webhooks.oxapay', [], true) ?? 
                                config('app.url') . '/api/v1/webhooks/oxapay',
            'returnUrl'      => $successUrl,
            'description'    => 'Pedido MTD Store #' . substr($order->uuid, 0, 8),
            'orderId'        => $order->uuid,
            'email'          => $order->customer->email ?? '',
        ]);

        if (!$response->successful()) {
            Log::error('OxaPay createCharge falhou', ['body' => $response->body()]);
            throw new \Exception('Falha ao criar cobrança cripto na OxaPay. Verifique as configurações.');
        }

        $data    = $response->json();
        $trackId = $data['trackId'] ?? uniqid('oxapay_');
        $payLink = $data['payLink'] ?? null;

        if (!$payLink) {
            throw new \Exception('OxaPay não retornou URL de pagamento.');
        }

        $order->update(['external_reference' => $trackId]);

        return new PaymentIntentDTO(
            externalReference: $trackId,
            checkoutUrl: $payLink,
            qrCode: null,
            gatewayName: 'oxapay'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // OxaPay pode usar HMAC SHA-512 ou simplesmente verificar o campo hmac no payload
        $payload = json_decode($request->getContent(), true);
        
        if (empty($this->webhookSecret)) {
            // Se não tiver secret configurado, aceita (restrinja por IP no servidor)
            return true;
        }

        $receivedHmac = $payload['hmac'] ?? '';
        $data = $payload;
        unset($data['hmac']);
        ksort($data);

        $calculatedHmac = hash_hmac('sha512', json_encode($data), $this->webhookSecret);

        return hash_equals($calculatedHmac, $receivedHmac);
    }

    public function handleWebhook(Request $request): WebhookEventDTO
    {
        $payload  = json_decode($request->getContent(), true);
        $status   = $payload['status'] ?? '';
        $trackId  = $payload['trackId'] ?? '';
        $orderId  = $payload['orderId'] ?? ''; // Este é nosso order->uuid

        // OxaPay statuses: waiting, confirming, confirmed, failed, expired
        $mappedStatus = match (strtolower($status)) {
            'confirmed' => 'paid',
            'failed', 'expired' => 'failed',
            default => 'pending',
        };

        return new WebhookEventDTO(
            gatewayName: 'oxapay',
            externalEventId: $trackId,
            orderUuid: $orderId,
            status: $mappedStatus
        );
    }

    public function refund(Order $order): bool
    {
        // OxaPay não suporta reembolso automático via API.
        // Reembolsos em cripto são feitos manualmente pelo merchant.
        Log::info("Reembolso OxaPay para pedido {$order->uuid} deve ser feito manualmente.");
        return false;
    }
}
