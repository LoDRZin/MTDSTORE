<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\Category;

class CategorizeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:categorize-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto categorize all products based on their names and descriptions, and remove unused categories';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Remove "UTILITÁRIOS" category
        $util = Category::where('name', 'LIKE', '%UTILIT%RIOS%')->orWhere('name', 'UTILITÁRIOS')->first();
        if ($util) {
            $util->products()->detach();
            $util->delete();
            $this->info("Categoria UTILITÁRIOS removida.");
        }

        // Get all categories to match
        $categories = Category::all();
        $map = [
            'spoofer' => ['spoofer', 'hwid', 'bypass', 'unban'],
            'internal' => ['internal', 'injetor', 'injected', 'dll'],
            'external' => ['external', 'overlay', 'diário', 'mensal'], // usually FiveM externals
            'fivem' => ['fivem', 'gta', 'cidade', 'rp'],
            'contas' => ['conta', 'account', 'netflix', 'spotify', 'serviço', 'assinatura', 'premium', 'discord', 'nitro'],
            'outros' => ['outro', 'miscellaneous', 'diversos', 'key'],
            'cheat' => ['cheat', 'hack', 'mod menu', 'aimbot', 'esp'],
        ];

        // Find the best category objects based on keywords
        $catObjects = [
            'spoofers' => $categories->first(fn($c) => stripos($c->name, 'spoofer') !== false),
            'internals_fivem' => $categories->first(fn($c) => stripos($c->name, 'internal') !== false && stripos($c->name, 'fivem') !== false),
            'externals_fivem' => $categories->first(fn($c) => stripos($c->name, 'external') !== false && stripos($c->name, 'fivem') !== false),
            'cheats_outros' => $categories->first(fn($c) => stripos($c->name, 'cheat') !== false && stripos($c->name, 'outro') !== false),
        $products = Product::all();
        $count = 0;
                    if ($catObjects['internals_fivem']) $assignedCategories[] = $catObjects['internals_fivem']->id;
                } else {
                    // Default to external if not explicitly internal for FiveM, or if explicitly external
                    if ($catObjects['externals_fivem']) $assignedCategories[] = $catObjects['externals_fivem']->id;
                }
            } 
            // Other cheats (Valorant, CSGO, etc)
            elseif ($this->hasKeyword($text, $map['cheat'])) {
                 if ($catObjects['cheats_outros']) $assignedCategories[] = $catObjects['cheats_outros']->id;
            }
            
            // Contas
            if ($this->hasKeyword($text, $map['contas'])) {
                if ($catObjects['contas']) $assignedCategories[] = $catObjects['contas']->id;
            }

            // Default to outros if nothing matched
            if (empty($assignedCategories)) {
                 $assignedCategories[] = $catObjects['outros']->id;
            }

            // Deduplicate and sync
            $assignedCategories = array_unique($assignedCategories);
            $product->categories()->sync($assignedCategories);
            $count++;
            
            $this->line("Categorizado: {$product->name} -> " . count($assignedCategories) . " categorias.");
        }

        $this->info("Concluído! {$count} produtos foram categorizados.");
        
        // Limpa o cache
        // Clear cache
        \Illuminate\Support\Facades\Cache::forget('categories.tree');
        $this->info("Cache de categorias invalidado.");
    }

    private function hasKeyword($text, $keywords)
    {
        foreach ($keywords as $kw) {
            if (stripos($text, $kw) !== false) return true;
        }
        return false;
    }
}
