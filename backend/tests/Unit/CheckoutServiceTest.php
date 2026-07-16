<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductStockItem;
use App\Models\Coupon;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\InventoryService;
use App\Services\CheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Exception;

class CheckoutServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CheckoutService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(CheckoutService::class);
        Redis::shouldReceive('set')->andReturn(true); // mock redis for inventory service update
        Redis::shouldReceive('get')->andReturn(null);
    }

    public function test_checkout_process_success()
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        
        // Add 2 stock items
        $inventoryService = app(InventoryService::class);
        $inventoryService->bulkImportKeys($product, "key1\nkey2");

        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];
        
        $order = $this->service->process($customer, $cartItems, null, 'stripe');

        $this->assertNotNull($order);
        $this->assertEquals(100.0, $order->total);
        $this->assertEquals('pending', $order->status);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 2);
        
        // Ensure stock items are sold
        $this->assertEquals(2, ProductStockItem::where('status', 'sold')->count());
        $this->assertEquals(0, ProductStockItem::where('status', 'available')->count());
    }

    public function test_checkout_fails_if_cart_total_has_errors()
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        // No stock added!
        
        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Estoque insuficiente');
        
        $this->service->process($customer, $cartItems, null, 'stripe');
    }

    public function test_checkout_with_coupon_redeems_coupon()
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        
        $inventoryService = app(InventoryService::class);
        $inventoryService->bulkImportKeys($product, "key1");

        $coupon = Coupon::factory()->create([
            'code' => 'TEST10',
            'type' => 'fixed',
            'value' => 10,
            'uses_count' => 0
        ]);

        $cartItems = [['product_id' => $product->id, 'quantity' => 1]];
        
        $order = $this->service->process($customer, $cartItems, 'TEST10', 'stripe');

        $this->assertEquals(40.0, $order->total); // 50 - 10
        $this->assertEquals(1, $coupon->fresh()->uses_count);
    }

    public function test_concurrent_checkout_throws_exception_if_stock_disappears()
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        
        $inventoryService = app(InventoryService::class);
        $inventoryService->bulkImportKeys($product, "key1");

        $cartItems = [['product_id' => $product->id, 'quantity' => 1]];
        
        // Mock CartService to always return success (simulating that AT THE START of the transaction, the stock was there)
        $cartTotalDto = new \App\DTOs\CartTotalDTO(50, 0, 50, null, []);
        $cartMock = \Mockery::mock(CartService::class);
        $cartMock->shouldReceive('calculateTotal')->andReturn($cartTotalDto);
        $this->app->instance(CartService::class, $cartMock);

        // NOW, someone else buys the key before our transaction gets to lockForUpdate
        ProductStockItem::where('product_id', $product->id)->update(['status' => 'sold']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Estoque indisponível para o produto #{$product->id} no momento da compra.");

        // Should fail here because lockForUpdate will return 0 items
        app(CheckoutService::class)->process($customer, $cartItems, null, 'stripe');
    }
}
