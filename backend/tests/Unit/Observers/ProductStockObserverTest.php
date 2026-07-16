<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductStockItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

class ProductStockObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_redis_is_updated_on_stock_creation()
    {
        $product = Product::factory()->create();

        Redis::shouldReceive('set')->once()->with("product_stock_count:{$product->id}", 1);

        ProductStockItem::factory()->create([
            'product_id' => $product->id,
            'status' => 'available'
        ]);
    }

    public function test_redis_is_updated_on_stock_status_change()
    {
        $product = Product::factory()->create();
        
        $item = ProductStockItem::factory()->create([
            'product_id' => $product->id,
            'status' => 'available'
        ]);

        Redis::shouldReceive('set')->once()->with("product_stock_count:{$product->id}", 0);

        $item->update(['status' => 'sold']);
    }
}
