<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'slug'      => $this->slug,
            'image_url' => $this->image_url,
            // Recursively load children if they were eager-loaded
            'children'  => CategoryResource::collection(
                $this->whenLoaded('activeChildren')
            ),
        ];
    }
}
