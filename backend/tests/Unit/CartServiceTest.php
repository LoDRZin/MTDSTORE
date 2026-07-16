<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Coupon;
use App\Services\CartService;
use App\Services\InventoryService;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $service;
    protected $inventoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->inventoryMock = Mockery::mock(InventoryService::class);
        $this->app->instance(InventoryService::class, $this->inventoryMock);
        
        // CouponService we can leave as real or mock, since it hits DB. Let's use real since we have RefreshDatabase.
        $this->service = app(CartService::class);
    }

    public function test_calculate_total_valid_cart()
    {
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        
        $this->inventoryMock->shouldReceive('getAvailableCount')->with($product->id)->andReturn(10);

        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];

        $dto = $this->service->calculateTotal($cartItems);

        $this->assertEquals(100.0, $dto->subtotal);
        $this->assertEquals(0.0, $dto->discount);
        $this->assertEquals(100.0, $dto->total);
        $this->assertEmpty($dto->errors);
    }

    public function test_calculate_total_with_coupon()
    {
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        $coupon = Coupon::factory()->create(['type' => 'percent', 'value' => 10, 'code' => 'PROMO10']);
        
        $this->inventoryMock->shouldReceive('getAvailableCount')->with($product->id)->andReturn(10);

        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];

        $dto = $this->service->calculateTotal($cartItems, 'PROMO10');

        $this->assertEquals(100.0, $dto->subtotal);
        $this->assertEquals(10.0, $dto->discount);
        $this->assertEquals(90.0, $dto->total);
        $this->assertEquals('PROMO10', $dto->couponCode);
        $this->assertEmpty($dto->errors);
    }

    public function test_calculate_total_insufficient_stock()
    {
        $product = Product::factory()->create(['price' => 50, 'status' => 'active']);
        
        $this->inventoryMock->shouldReceive('getAvailableCount')->with($product->id)->andReturn(1); // Only 1 available

        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];

        $dto = $this->service->calculateTotal($cartItems);

        $this->assertEquals(0.0, $dto->subtotal); // Since it failed, we didn't add it
        $this->assertNotEmpty($dto->errors);
        $this->assertStringContainsString('Estoque insuficiente', $dto->errors[0]);
    }
}
