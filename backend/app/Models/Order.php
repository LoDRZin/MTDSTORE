<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'uuid',
        'customer_id',
        'status',
        'total',
        'refunded_amount',
        'external_reference',
        'coupon_id',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'status' => 'string',
        'external_reference' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($order) {
            $validStatuses = ['pending', 'awaiting_payment', 'paid', 'failed', 'refunded', 'partially_refunded', 'chargeback'];
            if (!in_array($order->status, $validStatuses)) {
                throw new \InvalidArgumentException("Status de pedido inválido: {$order->status}");
            }
        });

        static::created(function ($order) {
            \App\Jobs\DispatchWebhookJob::dispatch('order.created', $order->toArray());
        });

        static::updated(function ($order) {
            if ($order->isDirty('status')) {
                \App\Jobs\DispatchWebhookJob::dispatch('order.' . $order->status, $order->toArray());
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
