<?php

namespace Tests\Unit\Gateway;

use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Gateways\EfiGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class EfiGatewayTest extends TestCase
{
    public function test_create_charge_returns_dto()
    {
        $order = new Order(['uuid' => 'test-uuid', 'total' => 100.00]);

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'fake_token'], 200),
            '*/v2/cob' => Http::response(['txid' => 'fake_txid', 'location' => 'qrcode'], 200),
        ]);

        $gateway = new EfiGateway();
        $dto = $gateway->createCharge($order);

        $this->assertEquals('fake_txid', $dto->externalReference);
        $this->assertEquals('efi', $dto->gatewayName);
    }
}
