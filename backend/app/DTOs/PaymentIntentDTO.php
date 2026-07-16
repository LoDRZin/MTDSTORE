<?php

namespace App\DTOs;

class PaymentIntentDTO
{
    public function __construct(
        public readonly string $externalReference,
        public readonly ?string $checkoutUrl,
        public readonly ?string $qrCode,
        public readonly string $gatewayName
    ) {}
}
