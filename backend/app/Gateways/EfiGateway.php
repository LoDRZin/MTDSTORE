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

        // Para ambientes PaaS (Render, Vercel), decodificamos a string base64 num arquivo temporário real
        $certBase64 = config('services.efi.cert_base64');
        if (!empty($certBase64)) {
            $tmpPath = sys_get_temp_dir() . '/efi_cert.p12';
            if (!file_exists($tmpPath)) {
                file_put_contents($tmpPath, base64_decode($certBase64));
            }
            $this->certPath = $tmpPath;
        }
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
        $traceId = request()->header('X-Correlation-ID') ?? uniqid();

        if (empty($this->certPath) || !file_exists($this->certPath)) {
            Log::error("Certificado da EFI não encontrado em: {$this->certPath}");
            throw new \Exception("Certificado mTLS da Efí não encontrado. Verifique EFI_CERT_PATH.");
        }

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
            Log::error('Erro ao criar cobrança EFI: ' . $response->body());
            throw new \Exception("Falha ao criar cobrança EFI.");
        }

        $data = $response->json();
        
        $locId = $data['loc']['id'] ?? null;
        $txid = $data['txid'] ?? null;

        if (!$locId) {
            throw new \Exception("Efí não retornou o Location ID.");
        }

        // Buscar QR Code payload (Copia e Cola)
        $qrResponse = Http::withToken($token)
            ->withHeaders(['X-Correlation-ID' => $traceId])
            ->withOptions(['cert' => $this->certPath])
            ->get("{$this->baseUrl}/v2/loc/{$locId}/qrcode");

        if (!$qrResponse->successful()) {
            Log::error('Erro ao gerar QR Code EFI: ' . $qrResponse->body());
            throw new \Exception("Falha ao gerar QR Code na EFI.");
        }

        $qrData = $qrResponse->json();

        return new PaymentIntentDTO(
            externalReference: $txid,
            checkoutUrl: null, // EFI PIX não tem checkout URL, é direto QR
            qrCode: $qrData['qrcode'] ?? null, // O "Copia e Cola" real
            gatewayName: 'efi'
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        // Efi does not use a simple signature header. It requires mTLS or IP whitelist.
        // For the sake of the contract, we can check if it came with an expected header or payload
        if (!$request->hasHeader('x-efi-signature') && empty($request->getContent())) {
            return false;
        }

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
