<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Str;

class TempSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'EXTERNAL FIVEM' => [
                'astrix external',
                'aspect external',
                'mtd elysium external',
                'randolas v3 external',
                'randolas v4 external',
                'lyra external',
                'china external',
            ],
            'SPOOFERS JOGOS' => [
                'dyck sp00fer',
                'sp00fer all games',
                'sp00fer fortnite 1click',
            ],
            'UTILITÁRIOS' => [
                'caixa p1x',
                'caixa misteriosa',
                'rockstar',
                'discord wl',
                'steam wl',
                'gta v instalavel',
                'ipvanish vpn',
                'mtd otimização',
                'm3mbros r3ais d1cord',
            ],
            'CH3ATS OUTROS JOGOS' => [
                'cs2 external',
                'bo7 external',
                'roblox external',
                'dayz external',
            ],
        ];

        foreach ($data as $categoryName => $productNames) {
            $category = Category::firstOrCreate([
                'name' => $categoryName
            ], [
                'slug' => Str::slug($categoryName),
                'description' => 'Categoria ' . $categoryName,
                'is_active' => \Illuminate\Support\Facades\DB::raw('true')
            ]);

            foreach ($productNames as $productName) {
                // Ensure unique slug
                $slug = Str::slug($productName);
                $originalSlug = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }

                $product = Product::create([
                    'name' => ucwords($productName),
                    'slug' => $slug,
                    'description' => 'Descrição automática para ' . $productName,
                    'price' => rand(10, 100),
                    'status' => 'active',
                    'delivery_type' => 'unique_key'
                ]);
                
                $product->categories()->syncWithoutDetaching([$category->id]);
            }
        }
    }
}
