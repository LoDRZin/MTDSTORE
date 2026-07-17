<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
