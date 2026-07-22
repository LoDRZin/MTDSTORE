<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Services\InventoryService;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inventoryService = app(\App\Services\InventoryService::class);
        // Usa o count carregado pela query ou tenta buscar via service como fallback
        $baseStock = isset($this->available_count) ? $this->available_count : $inventoryService->getAvailableCount($this->id);
        $availableCount = $this->delivery_type === 'file_download' ? PHP_INT_MAX : $baseStock;

        $displayPrice = (float) $this->price;
        if ($this->relationLoaded('variants') && $this->variants->isNotEmpty()) {
            $displayPrice = (float) $this->variants->min('price');
        }

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'image_url'       => $this->image_url 
                ? (str_starts_with($this->image_url, 'http') ? $this->image_url : Storage::url($this->image_url)) 
                : null,
            'price'           => $displayPrice,
            'has_variants'    => $this->relationLoaded('variants') && $this->variants->isNotEmpty(),
            'available_count' => $availableCount,
            'is_in_stock'     => $availableCount > 0,
            'categories'      => $this->whenLoaded('categories', fn () =>
                $this->categories->map(fn ($c) => [
                    'id'   => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])
            ),
            'variants'        => $this->whenLoaded('variants', fn () =>
                $this->variants->map(function ($v) use ($inventoryService) {
                    $variantStock = isset($v->available_count) ? $v->available_count : $inventoryService->getAvailableCount($this->id, $v->id);
                    return [
                        'id'              => $v->id,
                        'name'            => $v->name,
                        'price'           => (float) $v->price,
                        'available_count' => $variantStock,
                        'is_in_stock'     => $variantStock > 0,
                    ];
                })
            ),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
