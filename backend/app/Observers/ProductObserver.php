<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    public function updated(Product $product): void
    {
        if ($product->wasChanged(['price', 'status'])) {
            \App\Jobs\RevalidateStorefrontCache::dispatch("product-{$product->slug}");
        }
    }
}
