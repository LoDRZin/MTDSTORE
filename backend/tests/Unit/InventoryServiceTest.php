<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductStockItem;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
    }

    public function test_bulk_import_keys()
    {
        $product = Product::factory()->create();
        
        $rawText = "key1\nkey2\nkey3\n\nkey1\n  \n";

        // Mock Redis
        Redis::shouldReceive('set')
            ->once()
            ->with("product_stock_count:{$product->id}", 3);
            
        Redis::shouldReceive('get')
            ->once()
            ->with("product_stock_count:{$product->id}")
            ->andReturn(3);

        $count = $this->service->bulkImportKeys($product, $rawText);

        $this->assertEquals(3, $count);
        $this->assertDatabaseCount('product_stock_items', 3);
        $this->assertEquals(3, $this->service->getAvailableCount($product->id));
    }

    public function test_rollback_batch()
    {
        $product = Product::factory()->create();
        $rawText = "key1\nkey2";
        
        Redis::shouldReceive('set')->twice(); // 1 na importação, 1 no rollback

        $this->service->bulkImportKeys($product, $rawText);
        
        $batchId = ProductStockItem::first()->batch_id;
        $this->assertDatabaseCount('product_stock_items', 2);

        $deleted = $this->service->rollbackBatch($batchId);

        $this->assertEquals(2, $deleted);
        $this->assertDatabaseCount('product_stock_items', 0);
    }
}
