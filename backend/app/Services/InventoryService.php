<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStockItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class InventoryService
{
    /**
     * Import keys in bulk for a product.
     *
     * @param Product $product
     * @param string $rawText
     * @param int|null $addedBy
     * @param int|null $variantId
     * @return int Number of keys added
     */
    public function bulkImportKeys(Product $product, string $rawText, ?int $addedBy = null, ?int $variantId = null): int
    {
        $lines = explode("\n", str_replace("\r", "", $rawText));
        $keys = collect($lines)
            ->map(fn($line) => trim($line))
            ->filter(fn($line) => !empty($line))
            ->unique()
            ->values()
            ->all();

        if (empty($keys)) {
            return 0;
        }

        // Buscar todas as chaves existentes do produto para evitar duplicidade real
        // Como o campo é encriptado, precisamos descriptografar em memória (aceitável para volumes de estoque normais)
        $query = ProductStockItem::where('product_id', $product->id);
        
        if ($variantId) {
            $query->where('variant_id', $variantId);
        } else {
            $query->whereNull('variant_id');
        }

        $existingKeys = $query->get()
            ->map(function ($item) {
                try {
                    return Crypt::decryptString($item->value);
                } catch (\Exception $e) {
                    return null;
                }
            })
            ->filter()
            ->toArray();

        // Remove as chaves que já existem no banco de dados
        $keys = array_diff($keys, $existingKeys);

        if (empty($keys)) {
            return 0;
        }

        $batchId = (string) Str::uuid();
        $now = now();
        $records = [];
        
        foreach ($keys as $key) {
            $records[] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                // Criptografa manualmente, pois o bulk insert ignora os casts do Eloquent
                'value' => Crypt::encryptString($key),
                'status' => 'available',
                'added_by' => $addedBy,
                'batch_id' => $batchId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insere em lote (otimizado)
        ProductStockItem::insert($records);

        // Atualiza o cache
        $this->updateRedisCount($product->id, $variantId);

        activity()
            ->performedOn($product)
            ->log("Importou lote {$batchId} com " . count($keys) . " chaves");

        return count($keys);
    }

    /**
     * Get available count from Redis (with DB fallback).
     */
    public function getAvailableCount(int $productId, ?int $variantId = null): int
    {
        // Memory cache para evitar query N+1 no ProductResource
        static $productTypes = [];
        if (!array_key_exists($productId, $productTypes)) {
            $product = Product::find($productId);
            $productTypes[$productId] = $product ? $product->delivery_type : null;
        }

        // Se o produto for de download, o estoque é infinito
        if ($productTypes[$productId] === 'file_download') {
            return PHP_INT_MAX;
        }

        $redisKey = "product_stock_count:{$productId}" . ($variantId ? "_v{$variantId}" : "");

        try {
            $count = Cache::get($redisKey);

            if ($count === null) {
                $count = $this->updateRedisCount($productId, $variantId);
            }

            return (int) $count;
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Falha ao acessar Redis em getAvailableCount: " . $e->getMessage());
            // Redis não disponível — fallback direto ao banco
            $q = ProductStockItem::where('product_id', $productId)->where('status', 'available');
            if ($variantId) {
                $q->where('variant_id', $variantId);
            } else {
                $q->whereNull('variant_id');
            }
            return $q->count();
        }
    }

    /**
     * Rollback a specific batch of imported keys (only those still available).
     */
    public function rollbackBatch(string $batchId): int
    {
        $productIds = ProductStockItem::where('batch_id', $batchId)
            ->where('status', 'available')
            ->distinct()
            ->pluck('product_id');

        $deleted = ProductStockItem::where('batch_id', $batchId)
            ->where('status', 'available')
            ->delete();

        foreach ($productIds as $productId) {
            $this->updateRedisCount($productId);
        }

        return $deleted;
    }

    /**
     * Re-calculate and cache the exact stock count.
     */
    public function updateRedisCount(int $productId, ?int $variantId = null): int
    {
        $q = ProductStockItem::where('product_id', $productId)->where('status', 'available');
        if ($variantId) {
            $q->where('variant_id', $variantId);
        } else {
            $q->whereNull('variant_id');
        }
        $count = $q->count();

        try {
            $redisKey = "product_stock_count:{$productId}" . ($variantId ? "_v{$variantId}" : "");
            Cache::put($redisKey, $count);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Falha ao salvar no Redis em updateRedisCount: " . $e->getMessage());
            // Redis não disponível — continua sem cache
        }

        return $count;
    }
}
