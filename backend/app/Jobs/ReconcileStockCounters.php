<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductStockItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ReconcileStockCounters implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $products = Product::whereIn('delivery_type', ['keys', 'accounts'])->get();
        $corrected = 0;

        foreach ($products as $product) {
            $dbCount = ProductStockItem::where('product_id', $product->id)
                ->where('status', 'available')
                ->whereNull('variant_id')
                ->count();

            $redisKey = "product_stock_count:{$product->id}";
            $redisCount = (int) Cache::get($redisKey);

            if ($redisCount !== $dbCount) {
                Log::warning("ReconcileStockCounters: divergência no produto #{$product->id} ({$product->name}). Redis={$redisCount}, DB={$dbCount}. Corrigindo...");
                Cache::put($redisKey, $dbCount);
                $corrected++;
            }
        }

        Log::info("ReconcileStockCounters: concluído. {$corrected} produto(s) corrigido(s).");
    }
}
