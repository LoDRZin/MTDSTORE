<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function handle(Request $request, $gatewayName)
    {
        try {
            $gateway = \App\Gateways\PaymentGatewayFactory::make($gatewayName);
        } catch (\InvalidArgumentException $e) {
            abort(404, 'Gateway inválido');
        }

        // Validação de assinatura
        if (!$gateway->verifyWebhookSignature($request)) {
            abort(401, 'Assinatura inválida');
        }

        $eventDto = $gateway->handleWebhook($request);

        \App\Jobs\ProcessPaymentWebhook::dispatch($eventDto);

        return response()->json(['message' => 'Webhook recebido com sucesso']);
    }
}
