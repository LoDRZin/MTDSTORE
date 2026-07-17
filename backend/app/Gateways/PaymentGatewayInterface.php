<?php

namespace App\Gateways;

use App\Models\Order;
use App\DTOs\PaymentIntentDTO;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Cria uma intenção de cobrança no provedor de pagamento.
     *
     * @param Order $order
     * @return PaymentIntentDTO
     */
    public function createCharge(Order $order): PaymentIntentDTO;

    /**
     * Valida a assinatura de um webhook recebido.
     * Deve ser chamado antes de processar qualquer payload.
     *
     * @param Request $request
     * @return bool
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Processa o payload de um webhook validado.
     * Espera-se que lance exceções se falhar, caso contrário o processamento é considerado bem-sucedido.
     *
     * @param Request $request
     * @return WebhookEventDTO
     */
    public function handleWebhook(Request $request): \App\DTOs\WebhookEventDTO;

    /**
     * Reembolsa total ou parcialmente um pedido pago.
     *
     * @param Order $order
     * @return bool
     */
    public function refund(Order $order): bool;
}
