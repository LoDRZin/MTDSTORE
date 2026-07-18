<?php

namespace App\Gateways;

use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Resolve the requested gateway implementation.
     *
     * @param string $gatewayName
     * @return PaymentGatewayInterface
     * @throws InvalidArgumentException
     */
    public static function make(string $gatewayName): PaymentGatewayInterface
    {
        return match ($gatewayName) {
            'stripe'      => app(StripeGateway::class),
            'mercadopago' => app(MercadoPagoGateway::class),
            'efi'         => app(EfiGateway::class),
            'oxapay'      => app(OxaPayGateway::class),
            'wise'        => app(WiseGateway::class),
            default       => throw new InvalidArgumentException("Gateway não suportado: {$gatewayName}"),
        };
    }
}
