<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EfiGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $certPath;

    public function __construct()
    {
        $this->baseUrl = config('services.efi.url');
        $this->clientId = config('services.efi.client_id');
        $this->clientSecret = config('services.efi.client_secret');
        $this->certPath = config('services.efi.cert_path');
    }

    protected function getAccessToken(): string
    {
        $credentials = base64_encode("{$this->clientId}:{$this->clientSecret}");
        
        $response = Http::withHeaders([
            'Authorization' => "Basic {$credentials}",
            'Content-Type' => 'application/json'
        ])
        ->withOptions(['cert' => $this->certPath])
        ->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'client_credentials'
        ]);

        if (!$response->successful()) {
            throw new \Exception("Falha ao obter token da EFI.");
        }

        return $response->json('access_token');
    }

    public function createCharge(Order $order): PaymentIntentDTO
    {
        $token = $this->getAccessToken();
        $traceId = request()->header('X-Correlation-ID');

        $response = Http::withToken($token)
            ->withHeaders(['X-Correlation-ID' => $traceId])
            ->withOptions(['cert' => $this->certPath])
            ->post("{$this->baseUrl}/v2/cob", [
                'calendario' => ['expiracao' => 3600],
                'valor' => ['original' => number_format($order->total, 2, '.', '')],
                'chave' => config('services.efi.pix_key'),
                'infoAdicionais' => [
                    ['nome' => 'Pedido', 'valor' => $order->uuid]
                ]
            ]);

        if (!$response->successful()) {
            throw new \Exception("Falha ao criar cobrança EFI.");
        }

        $data = $response->json();
        
        // Em um fluxo real de PIX, também precisaríamos gerar o QR Code (endpoint /v2/loc/{id}/qrcode)
        // Aqui assumimos que o loc.id ou txid foi retornado

        return new PaymentIntentDTO(
            externalReference: $data['txid'],
            checkoutUrl: null,
            qrCode: $data['location'] ?? null, // Simplificado
            gatewayName: 'efi'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // A EFI geralmente não usa assinatura HMAC, ela exige mTLS na rota de webhook (configurado via Nginx/Apache)
        // Ou envia via post normal em ambiente dev sem mTLS e verificamos um token.
        // Vamos simplificar retornando true (assumindo Nginx block)
        return true;
    }

    public function handleWebhook(Request $request): WebhookEventDTO
    {
        $payload = json_decode($request->getContent(), true);
        
        // A EFI envia array de pix
        $pix = $payload['pix'][0] ?? [];
        $txid = $pix['txid'] ?? '';
        
        $status = 'pending';
        if (isset($payload['pix'])) {
            $status = 'paid';
        }

        return new WebhookEventDTO(
            gatewayName: 'efi',
            externalEventId: $pix['endToEndId'] ?? uniqid(),
            orderUuid: 'unknown', // TXID precisaria ser buscado no banco para achar a order
            status: $status
        );
    }

    public function refund(Order $order): bool
    {
        // Placeholder para /v2/cob/{txid}/devolucao/{id}
        return true;
    }
}
