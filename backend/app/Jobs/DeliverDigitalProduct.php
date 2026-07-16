<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class DeliverDigitalProduct implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        public readonly Order $order
    ) {}

    public function handle(): void
    {
        // For security, we do not send the keys directly in the email.
        // We generate a signed URL valid for 48 hours.
        $signedUrl = URL::temporarySignedRoute(
            'orders.success', 
            now()->addHours(48), 
            ['order' => $this->order->uuid]
        );

        // 2. Enviar E-mail
        \Illuminate\Support\Facades\Mail::to($this->order->customer->email ?? $this->order->customer_email)->send(
            new \App\Mail\DigitalProductDelivered($this->order, $signedUrl)
        );
    }

    /**
     * Lida com a falha do job.
     */
    public function failed(\Throwable $exception): void
    {
        // Enviar alerta para o administrador (exemplo via log, mas poderia ser Slack/Email)
        \Illuminate\Support\Facades\Log::critical("ALERTA CRÍTICO: Falha ao entregar pedido {$this->order->uuid}.", [
            'error' => $exception->getMessage(),
            'order_id' => $this->order->id
        ]);
        
        // Exemplo: notificar via email
        // \Illuminate\Support\Facades\Mail::raw("Falha ao entregar pedido {$this->order->uuid}. Erro: " . $exception->getMessage(), function ($message) {
        //     $message->to('admin@mtdstore.com')->subject('ALERTA: Falha de Entrega');
        // });
    }
}
