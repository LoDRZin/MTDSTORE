<?php

namespace App\Gateways;

use App\Models\Order;
use Illuminate\Http\Request;
use App\DTOs\PaymentIntentDTO;
use App\DTOs\WebhookEventDTO;

interface PaymentGatewayInterface
{
    /**
     * Create a payment charge/intent for the given order.
     *
     * @param Order $order
     * @return PaymentIntentDTO
     */
    public function createCharge(Order $order): PaymentIntentDTO;

    /**
     * Verify the webhook signature from the gateway.
     *
     * @param Request $request
     * @return bool
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Handle the incoming webhook and return a standard DTO.
     *
     * @param Request $request
     * @return WebhookEventDTO
     */
    public function handleWebhook(Request $request): WebhookEventDTO;

    /**
     * Refund a previously paid order.
     *
     * @param Order $order
     * @return bool
     */
    public function refund(Order $order): bool;
}
