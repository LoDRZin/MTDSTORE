<?php

namespace App\Observers;

use App\Models\ProductStockItem;

class ProductStockObserver
{
    public function created(ProductStockItem $productStockItem): void
    {
        if ($productStockItem->status === 'available') {
            \Illuminate\Support\Facades\Redis::incr("product_stock_count:{$productStockItem->product_id}");
        }
    }

    public function updated(ProductStockItem $productStockItem): void
    {
        if ($productStockItem->wasChanged('status')) {
            $oldStatus = $productStockItem->getOriginal('status');
            $newStatus = $productStockItem->status;

            if ($oldStatus === 'available' && $newStatus !== 'available') {
                \Illuminate\Support\Facades\Redis::decr("product_stock_count:{$productStockItem->product_id}");
            } elseif ($oldStatus !== 'available' && $newStatus === 'available') {
                \Illuminate\Support\Facades\Redis::incr("product_stock_count:{$productStockItem->product_id}");
            }
        }
    }

    public function deleted(ProductStockItem $productStockItem): void
    {
        if ($productStockItem->status === 'available') {
            \Illuminate\Support\Facades\Redis::decr("product_stock_count:{$productStockItem->product_id}");
        }
    }
}
