<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Review;
use App\Models\ReviewSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class SendReviewRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function handle(): void
    {
        if (! ReviewSetting::current()->enabled || ! $this->order->customer?->email) return;
        foreach ($this->order->items()->with('product:id,name')->get()->unique('product_id') as $item) {
            if (Review::where('order_id', $this->order->id)->where('product_id', $item->product_id)->exists()) continue;
            $url = URL::temporarySignedRoute('reviews.form', now()->addDays(14), ['order' => $this->order->uuid, 'product' => $item->product_id]);
            Mail::raw("Como foi sua experiência com {$item->product->name}? Avalie em: {$url}", fn ($mail) => $mail->to($this->order->customer->email)->subject('Avalie sua compra'));
        }
    }
}
