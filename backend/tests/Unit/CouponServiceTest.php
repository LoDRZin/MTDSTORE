<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Exception;

class CouponServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CouponService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CouponService();
    }

    public function test_validate_valid_percent_coupon()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'percent',
            'value' => 10,
        ]);

        $dto = $this->service->validate($coupon->code, 100.0);

        $this->assertEquals(10.0, $dto->discountValue);
        $this->assertEquals('percent', $dto->type);
    }

    public function test_validate_valid_fixed_coupon()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'fixed',
            'value' => 15,
        ]);

        $dto = $this->service->validate($coupon->code, 50.0);

        $this->assertEquals(15.0, $dto->discountValue);
    }

    public function test_discount_cannot_be_greater_than_order_total()
    {
        $coupon = Coupon::factory()->create([
            'type' => 'fixed',
            'value' => 50,
        ]);

        $dto = $this->service->validate($coupon->code, 30.0);

        $this->assertEquals(30.0, $dto->discountValue); // Capped at total
    }

    public function test_validate_expired_coupon_throws_exception()
    {
        $coupon = Coupon::factory()->create([
            'expires_at' => now()->subDay(),
        ]);

        $this->expectException(Exception::class);
        $this->service->validate($coupon->code, 100.0);
    }

    public function test_validate_exhausted_coupon_throws_exception()
    {
        $coupon = Coupon::factory()->create([
            'max_uses' => 5,
            'uses_count' => 5,
        ]);

        $this->expectException(Exception::class);
        $this->service->validate($coupon->code, 100.0);
    }

    public function test_redeem_increments_uses_count()
    {
        $coupon = Coupon::factory()->create([
            'uses_count' => 0,
        ]);

        $this->service->redeem($coupon);

        $this->assertEquals(1, $coupon->fresh()->uses_count);
    }
}
