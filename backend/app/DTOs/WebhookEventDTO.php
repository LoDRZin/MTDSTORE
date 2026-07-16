<?php

namespace App\DTOs;

class WebhookEventDTO
{
    public function __construct(
        public readonly string $gatewayName,
        public readonly string $externalEventId,
        public readonly string $orderUuid,
        public readonly string $status // paid, failed, etc.
    ) {}
}
