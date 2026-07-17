<?php

namespace Tests\Unit\Gateway;

use Tests\TestCase;
use App\Gateways\PaymentGatewayInterface;
use App\Gateways\PaymentGatewayFactory;
use App\Gateways\StripeGateway;
use App\Gateways\MercadoPagoGateway;
use App\Gateways\EfiGateway;
use Illuminate\Http\Request;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class PaymentGatewayContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'services.stripe.secret' => 'sk_test_123',
            'services.stripe.webhook_secret' => 'whsec_123',
            'services.mercadopago.access_token' => 'APP_USR-123',
            'services.mercadopago.webhook_secret' => 'mp_sec_123',
            'services.efi.url' => 'https://api-homologacao.efipay.com.br',
            'services.efi.client_id' => 'Client_Id_123',
            'services.efi.client_secret' => 'Client_Secret_123',
            'services.efi.cert_path' => 'path/to/cert.pem',
        ]);
    }

    #[DataProvider('gatewayProvider')]
    public function test_gateways_implement_interface(string $gatewayName, string $expectedClass)
    {
        $gateway = PaymentGatewayFactory::make($gatewayName);
        
        $this->assertInstanceOf(PaymentGatewayInterface::class, $gateway);
        $this->assertInstanceOf($expectedClass, $gateway);
    }

    public function test_factory_throws_exception_on_invalid_gateway()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gateway não suportado: pagseguro');
        
        PaymentGatewayFactory::make('pagseguro');
    }

    public function test_stripe_verify_signature_fails_on_invalid_payload()
    {
        $gateway = PaymentGatewayFactory::make('stripe');
        
        $request = Request::create('/webhooks/stripe', 'POST', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 'invalid_signature'
        ], json_encode(['id' => 'evt_123']));
        
        $this->assertFalse($gateway->verifyWebhookSignature($request));
    }

    public function test_mercadopago_verify_signature_fails_on_invalid_payload()
    {
        $gateway = PaymentGatewayFactory::make('mercadopago');
        
        $request = Request::create('/webhooks/mercadopago', 'POST', [], [], [], [
            'HTTP_X_SIGNATURE' => 'ts=123,v1=invalid_signature',
            'HTTP_X_REQUEST_ID' => 'req_123'
        ], json_encode(['action' => 'payment.created']));
        
        $this->assertFalse($gateway->verifyWebhookSignature($request));
    }

    public function test_efi_verify_signature_fails_on_invalid_payload()
    {
        $gateway = PaymentGatewayFactory::make('efi');
        
        // Efi webhook is tricky, often it uses mTLS or an IP whitelist rather than a simple signature header,
        // but if it uses HMAC or some validation, this contract requires it to return boolean.
        $request = Request::create('/webhooks/efi', 'POST');
        
        $this->assertFalse($gateway->verifyWebhookSignature($request));
    }

    public static function gatewayProvider(): array
    {
        return [
            ['stripe', StripeGateway::class],
            ['mercadopago', MercadoPagoGateway::class],
            ['efi', EfiGateway::class],
        ];
    }
}
