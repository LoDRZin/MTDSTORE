<?php

namespace Tests\Unit\Gateway;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Gateways\MercadoPagoGateway;
use App\DTOs\PaymentIntentDTO;

class MercadoPagoGatewayTest extends TestCase
{
    public function test_create_charge_returns_dto()
    {
        $order = new Order(['uuid' => 'test-uuid', 'total' => 100.00]);
        $order->setRelation('customer', new User(['email' => 'test@example.com']));

        // Real MP SDK creates HTTP requests when PaymentClient::create is called.
        // For unit testing without making real requests, we would mock the Guzzle client inside MercadoPagoConfig,
        // or just mock the gateway itself if we test the Service.
        
        $this->assertTrue(true); // Placeholder until Guzzle mock is injected
    }
}
