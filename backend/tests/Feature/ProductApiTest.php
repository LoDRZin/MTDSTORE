<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductStockItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_active_products()
    {
        Product::factory()->count(15)->create(['status' => 'active']);
        Product::factory()->create(['status' => 'draft']);

        Redis::shouldReceive('get')->andReturn(10); // Mock Redis stock count

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
                 ->assertJsonCount(12, 'data'); // Paginated to 12
    }

    public function test_can_search_products_by_name()
    {
        Product::factory()->create(['name' => 'Minecraft Java Edition', 'status' => 'active']);
        Product::factory()->create(['name' => 'GTA V', 'status' => 'active']);

        Redis::shouldReceive('get')->andReturn(0);

        $response = $this->getJson('/api/products?search=Minecraft');

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data')
                 ->assertJsonFragment(['name' => 'Minecraft Java Edition']);
    }
}
