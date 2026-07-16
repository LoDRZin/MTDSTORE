<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductStockItem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ReconcileStockCounters implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('default');
    }

    /**
     * Reconta os itens disponíveis no banco para cada produto
     * e corrige o valor no Redis caso haja divergência.
     */
    public function handle(): void
    {
        $products = Product::all('id', 'name');
        $corrected = 0;

        foreach ($products as $product) {
            $dbCount = ProductStockItem::where('product_id', $product->id)
                ->where('status', 'available')
                ->count();

            $redisKey = "product_stock_count:{$product->id}";
            $redisCount = (int) Redis::get($redisKey);

            if ($redisCount !== $dbCount) {
                Log::warning("ReconcileStockCounters: divergência no produto #{$product->id} ({$product->name}). Redis={$redisCount}, DB={$dbCount}. Corrigindo...");
                Redis::set($redisKey, $dbCount);
                $corrected++;
            }
        }

        Log::info("ReconcileStockCounters: concluído. {$corrected} produto(s) corrigido(s).");
    }
}
