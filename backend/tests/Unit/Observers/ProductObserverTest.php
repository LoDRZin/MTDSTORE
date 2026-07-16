<?php

namespace Tests\Unit\Observers;

use Tests\TestCase;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use App\Jobs\RevalidateStorefrontCache;

class ProductObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_revalidation_job_on_price_change()
    {
        Queue::fake();

        $product = Product::factory()->create([
            'price' => 100,
            'status' => 'draft'
        ]);

        $product->update(['price' => 150]);

        Queue::assertPushed(RevalidateStorefrontCache::class, function ($job) use ($product) {
            return $job->tagOrPath === "product-{$product->slug}";
        });
    }

    public function test_does_not_dispatch_job_if_irrelevant_field_changed()
    {
        Queue::fake();

        $product = Product::factory()->create([
            'description' => 'Old description'
        ]);

        $product->update(['description' => 'New description']);

        Queue::assertNotPushed(RevalidateStorefrontCache::class);
    }
}
