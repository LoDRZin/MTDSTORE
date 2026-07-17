<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class ProductStockItem extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_id',
        'variant_id',
        'value',
        'status',
        'order_item_id',
        'added_by',
        'batch_id',
    ];

    protected $casts = [
        'value' => 'encrypted',
        'status' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($item) {
            $validStatuses = ['available', 'sold', 'revoked'];
            if (!in_array($item->status, $validStatuses)) {
                throw new \InvalidArgumentException("Status de estoque inválido: {$item->status}");
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status'])
            ->logOnlyDirty();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
