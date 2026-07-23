<?php

namespace App\Observers;

use App\Models\ProductStockItem;
use Illuminate\Support\Facades\Cache;

class ProductStockObserver
{
    public function created(ProductStockItem $productStockItem): void
    {
        if ($productStockItem->status === 'available') {
            Cache::increment("product_stock_count:{$productStockItem->product_id}");
        }
    }

    public function updated(ProductStockItem $productStockItem): void
    {
        if ($productStockItem->wasChanged('status')) {
            $oldStatus = $productStockItem->getOriginal('status');
            $newStatus = $productStockItem->status;

            if ($oldStatus === 'available' && $newStatus !== 'available') {
                Cache::decrement("product_stock_count:{$productStockItem->product_id}");
            } elseif ($oldStatus !== 'available' && $newStatus === 'available') {
                Cache::increment("product_stock_count:{$productStockItem->product_id}");
            }
        }
    }

    public function deleted(ProductStockItem $productStockItem): void
    {
        if ($productStockItem->status === 'available') {
            Cache::decrement("product_stock_count:{$productStockItem->product_id}");
        }
    }
}
