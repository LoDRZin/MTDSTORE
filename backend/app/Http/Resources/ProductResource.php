<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
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
        $inventoryService = app(InventoryService::class);
        $availableCount = $inventoryService->getAvailableCount($this->id);

        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'slug'            => $this->slug,
            'description'     => $this->description,
            'image_url'       => $this->image_url,
            'price'           => (float) $this->price,
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
                    $variantStock = $inventoryService->getAvailableCount($this->id, $v->id);
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
