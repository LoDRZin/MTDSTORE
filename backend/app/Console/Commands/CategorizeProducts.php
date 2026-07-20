<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CategorizeProducts extends Command
{
    protected $signature = 'data:categorize-products';

    protected $description = 'Categoriza produtos a partir do nome e da descriÃ§Ã£o.';

    public function handle(): int
    {
        $categories = Category::query()->get();
        $keywords = [
            'spoofer' => ['spoofer', 'hwid', 'bypass', 'unban'],
            'internal' => ['internal', 'injetor', 'injected', 'dll'],
            'external' => ['external', 'overlay', 'diÃ¡rio', 'mensal'],
            'fivem' => ['fivem', 'gta', 'cidade', 'rp'],
            'contas' => ['conta', 'account', 'netflix', 'spotify', 'serviÃ§o', 'assinatura', 'premium', 'discord', 'nitro'],
            'outros' => ['outro', 'miscellaneous', 'diversos', 'key'],
            'cheat' => ['cheat', 'hack', 'mod menu', 'aimbot', 'esp'],
        ];

        $categoryByKeyword = [
            'spoofer' => $this->findCategory($categories, 'spoofer'),
            'internal' => $this->findCategory($categories, 'internal', 'fivem'),
            'external' => $this->findCategory($categories, 'external', 'fivem'),
            'cheat' => $this->findCategory($categories, 'cheat', 'outro'),
            'contas' => $this->findCategory($categories, 'conta'),
            'outros' => $this->findCategory($categories, 'outro'),
        ];

        $count = 0;
        Product::query()->with('categories')->each(function (Product $product) use ($keywords, $categoryByKeyword, &$count): void {
            $text = mb_strtolower($product->name . ' ' . ($product->description ?? ''));
            $assigned = [];

            if ($this->hasKeyword($text, $keywords['spoofer']) && $categoryByKeyword['spoofer']) {
                $assigned[] = $categoryByKeyword['spoofer']->id;
            }

            if ($this->hasKeyword($text, $keywords['fivem'])) {
                $isInternal = $this->hasKeyword($text, $keywords['internal']);
                $category = $isInternal ? $categoryByKeyword['internal'] : $categoryByKeyword['external'];
                if ($category) {
                    $assigned[] = $category->id;
                }
            } elseif ($this->hasKeyword($text, $keywords['cheat']) && $categoryByKeyword['cheat']) {
                $assigned[] = $categoryByKeyword['cheat']->id;
            }

            if ($this->hasKeyword($text, $keywords['contas']) && $categoryByKeyword['contas']) {
                $assigned[] = $categoryByKeyword['contas']->id;
            }

            if ($assigned === [] && $categoryByKeyword['outros']) {
                $assigned[] = $categoryByKeyword['outros']->id;
            }

            if ($assigned !== []) {
                $product->categories()->sync(array_unique($assigned));
                $count++;
            }
        });

        Cache::forget('categories.tree');
        $this->info("ConcluÃ­do: {$count} produtos categorizados.");

        return self::SUCCESS;
    }

    private function findCategory($categories, string ...$terms): ?Category
    {
        return $categories->first(fn (Category $category) => collect($terms)
            ->every(fn (string $term) => str_contains(mb_strtolower($category->name), $term)));
    }

    private function hasKeyword(string $text, array $keywords): bool
    {
        return collect($keywords)->contains(fn (string $keyword) => str_contains($text, $keyword));
    }
}
