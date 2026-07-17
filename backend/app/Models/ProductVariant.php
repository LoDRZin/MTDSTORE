<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'price',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockItems()
    {
        return $this->hasMany(ProductStockItem::class, 'variant_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::saved(function ($variant) {
            if ($variant->product) {
                $variant->product->updateBasePrice();
            }
        });

        static::deleted(function ($variant) {
            if ($variant->product) {
                $variant->product->updateBasePrice();
            }
        });
    }
}
