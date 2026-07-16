<?php

namespace App\Jobs;

use App\Mail\DigitalProductDelivered;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDeliveryEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(
        public readonly Order $order,
        public readonly string $signedUrl
    ) {}

    public function handle(): void
    {
        Mail::to($order->customer->email ?? 'test@example.com')
            ->send(new DigitalProductDelivered($this->order, $this->signedUrl));
    }
}
