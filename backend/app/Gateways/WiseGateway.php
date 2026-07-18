<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Wise Gateway — Transferências Internacionais
 *
 * A Wise não é um gateway tradicional com checkout automático.
 * O fluxo é:
 *  1. createCharge() → retorna os dados da conta Wise para o cliente transferir
 *  2. Frontend exibe um modal com os dados bancários e o valor exato
 *  3. Admin confirma manualmente no painel OU usa webhook via API da Wise para detectar recebimento
 *
 * Para ativar:
 *  - Crie uma conta Business em https://wise.com
 *  - Gere uma API Key (Personal Tokens em https://wise.com/settings/tokens)
 *  - Anote o Profile ID (disponível via GET /v1/profiles após gerar a API key)
 *  - Configure WISE_API_KEY, WISE_PROFILE_ID e WISE_ACCOUNT_EMAIL no .env
 *  - Para webhooks automáticos, configure WISE_WEBHOOK_SECRET e
 *    adicione a URL https://mtdstore.onrender.com/api/v1/webhooks/wise no painel da Wise
 */
class WiseGateway implements PaymentGatewayInterface
{
    protected string $apiKey;
    protected string $profileId;
    protected string $accountEmail;
    protected string $webhookSecret;
    protected string $baseUrl;

    public function __construct()
    {
        $isSandbox          = config('services.wise.sandbox', true);
        $this->baseUrl      = $isSandbox
            ? 'https://api.sandbox.transferwise.tech'
            : 'https://api.transferwise.com';
        $this->apiKey       = config('services.wise.api_key', '');
        $this->profileId    = config('services.wise.profile_id', '');
        $this->accountEmail = config('services.wise.account_email', '');
        $this->webhookSecret = config('services.wise.webhook_secret', '');
    }

    public function createCharge(Order $order): PaymentIntentDTO
    {
        // Gera uma referência única para rastrear a transferência
        $reference = 'MTD-' . strtoupper(substr($order->uuid, 0, 8));
        
        $order->update(['external_reference' => $reference]);

        // Retorna os dados bancários para o cliente realizar a transferência.
        // O frontend vai exibir essas informações num modal bonitinho.
        // O campo qrCode aqui é usado para passar dados extras ao frontend como JSON.
        $wiseData = json_encode([
            'account_email'  => $this->accountEmail ?: 'configure@seuwise.com',
            'reference'      => $reference,
            'amount'         => number_format((float) $order->total, 2, '.', ''),
            'currency'       => 'BRL',
            'instructions'   => 'Envie exatamente o valor acima via Wise para o e-mail informado. Use a referência como descrição da transferência.',
        ]);

        return new PaymentIntentDTO(
            externalReference: $reference,
            checkoutUrl: null,     // Sem redirecionamento — exibimos os dados na própria tela
            qrCode: $wiseData,     // Reaproveitamos o campo qrCode para passar os dados ao frontend
            gatewayName: 'wise'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        if (empty($this->webhookSecret)) {
            // Sem secret configurado, aceita (útil durante testes)
            return true;
        }

        // Wise usa SHA-256 HMAC
        $signature = $request->header('X-Signature-SHA256', '');
        $payload   = $request->getContent();
        $expected  = base64_encode(hash_hmac('sha256', $payload, $this->webhookSecret, true));

        return hash_equals($expected, $signature);
    }

    public function handleWebhook(Request $request): WebhookEventDTO
    {
        $payload  = json_decode($request->getContent(), true);
        $eventType = $payload['event_type'] ?? $payload['type'] ?? '';
        
        // Wise webhook para recebimento de transferência
        // event_type: "transfers#state-change" com current_state: "outgoing_payment_sent"
        $resource  = $payload['data']['resource'] ?? $payload['resource'] ?? [];
        $reference = $resource['reference'] ?? '';
        $state     = $resource['current_state'] ?? $resource['status'] ?? '';

        // Mapeamento de estados da Wise
        $mappedStatus = match (true) {
            str_contains($state, 'funds_converted') ||
            str_contains($state, 'outgoing_payment_sent') => 'paid',
            str_contains($state, 'cancelled') ||
            str_contains($state, 'bounced_back') => 'failed',
            default => 'pending',
        };

        // A referência é "MTD-XXXXXXXX" — precisamos encontrar o order_uuid pelo external_reference
        $order = \App\Models\Order::where('external_reference', $reference)->first();
        $orderUuid = $order?->uuid ?? '';

        return new WebhookEventDTO(
            gatewayName: 'wise',
            externalEventId: $payload['id'] ?? uniqid('wise_'),
            orderUuid: $orderUuid,
            status: $mappedStatus
        );
    }

    public function refund(Order $order): bool
    {
        // Reembolsos via Wise são feitos pelo painel web ou API separada
        Log::info("Reembolso Wise para pedido {$order->uuid} deve ser iniciado manualmente via painel Wise.");
        return false;
    }
}
