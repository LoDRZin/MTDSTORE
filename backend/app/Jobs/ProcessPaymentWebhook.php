<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

use App\DTOs\WebhookEventDTO;
use App\Models\Order;
use App\Models\WebhookEvent;
use App\Services\CheckoutService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    public $tries = 5;

    public function __construct(
        public readonly WebhookEventDTO $eventDto
    ) {}

    public function handle(CheckoutService $checkoutService): void
    {
        // 1. Idempotency Check
        $exists = WebhookEvent::where('gateway', $this->eventDto->gatewayName)
            ->where('external_event_id', $this->eventDto->externalEventId)
            ->exists();

        if ($exists) {
            Log::info("Webhook idempotency: Event {$this->eventDto->externalEventId} already processed.");
            return;
        }

        DB::transaction(function () use ($checkoutService) {
            $order = Order::where('uuid', $this->eventDto->orderUuid)->lockForUpdate()->first();

            if (!$order) {
                Log::error("Order {$this->eventDto->orderUuid} not found for webhook.");
                return;
            }

            if ($this->eventDto->status === 'paid' && $order->status !== 'paid') {
                try {
                    // Ordena os itens por product_id para garantir que múltiplas transações concorrentes
                    // sempre travem as linhas na mesma ordem, eliminando o risco de Deadlocks.
                    $sortedItems = $order->items->sortBy('product_id');

                    // Alocação de chaves (lock pessimista)
                    foreach ($sortedItems as $orderItem) {
                        $stockItem = \App\Models\ProductStockItem::where('product_id', $orderItem->product_id)
                            ->where('status', 'available')
                            ->lockForUpdate()
                            ->first();

                        if (!$stockItem) {
                            throw new Exception("Estoque insuficiente para o produto #{$orderItem->product_id}");
                        }

                        $stockItem->update([
                            'status' => 'sold',
                            'order_item_id' => $orderItem->id,
                        ]);

                        $orderItem->update(['stock_item_id' => $stockItem->id]);

                        $inventoryService = app(\App\Services\InventoryService::class);
                        $inventoryService->updateRedisCount($orderItem->product_id);
                        
                        if ($inventoryService->getAvailableCount($orderItem->product_id) === 0) {
                            dispatch(new RevalidateStorefrontCache());
                        }
                    }

                    $order->update(['status' => 'paid']);
                    
                    // Redeem coupon if exists
                    if ($order->coupon_id && $order->coupon) {
                        try {
                            $couponService = app(\App\Services\CouponService::class);
                            $couponService->redeem($order->coupon);
                        } catch (Exception $e) {
                            Log::warning("Não foi possível redimir o cupom #{$order->coupon_id} do pedido {$order->uuid}: " . $e->getMessage());
                        }
                    }

                    // Dispatch Delivery
                    dispatch(new DeliverDigitalProduct($order));

                } catch (Exception $e) {
                    // OutOfStockException or other errors
                    Log::error("Payment confirmed but failed to deliver: " . $e->getMessage());
                    
                    // Acionar reembolso automático
                    $gateway = \App\Gateways\PaymentGatewayFactory::make($this->eventDto->gatewayName);
                    $gateway->refund($order);
                    
                    $order->update(['status' => 'refunded']);

                    // Invalida permanentemente as chaves que estavam atreladas a este pedido
                    $orderItemIds = $order->items()->pluck('id');
                    \App\Models\ProductStockItem::whereIn('order_item_id', $orderItemIds)
                        ->update(['status' => 'revoked']);
                }
            } elseif ($this->eventDto->status === 'failed') {
                $order->update(['status' => 'failed']);
            }

            WebhookEvent::create([
                'gateway' => $this->eventDto->gatewayName,
                'external_event_id' => $this->eventDto->externalEventId,
                'processed_at' => now(),
            ]);
        });
    }

    /**
     * Lida com a falha do job.
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("ALERTA CRÍTICO: Falha ao processar webhook {$this->eventDto->externalEventId} do gateway {$this->eventDto->gatewayName}.", [
            'error' => $exception->getMessage(),
            'order_uuid' => $this->eventDto->orderUuid ?? null,
        ]);
        
        // Exemplo: notificar via email
        // \Illuminate\Support\Facades\Mail::raw("Falha repetida no webhook {$this->eventDto->externalEventId}. Erro: " . $exception->getMessage(), function ($message) {
        //     $message->to('admin@mtdstore.com')->subject('ALERTA: Falha no Webhook de Pagamento');
        // });
    }
}
