<?php

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;

// Deletar Utilitarios
$utilitarios = Category::where('slug', 'utilitarios')->first();
if ($utilitarios) {
    $utilitarios->delete();
    echo "Categoria Utilitários deletada.\n";
}

$categories = Category::all();

$mapping = [
    'fivem' => ['external', 'internal', 'menu', 'astrix', 'rockstar', 'fivem'],
    'spoofers' => ['spoofer', 'hwid'],
    'outros jogos' => ['valorant', 'cs2', 'fortnite', 'bloodstrike', 'rust', 'apex'],
    'contas' => ['conta', 'smurf', 'verificada', 'netflix', 'spotify'],
];

$products = Product::all();
$count = 0;

foreach ($products as $p) {
    $name = strtolower($p->name);
    $desc = strtolower($p->description);
    
    // Clear existing categories
    $p->categories()->detach();
    
    $assignedCat = null;
    
    if (str_contains($name, 'spoofer') || str_contains($desc, 'spoofer')) {
        $assignedCat = $categories->first(fn($c) => str_contains(strtolower($c->name), 'spoofer'));
    }
    elseif (str_contains($name, 'conta') || str_contains($name, 'verificada')) {
        $assignedCat = $categories->first(fn($c) => str_contains(strtolower($c->name), 'contas'));
    }
    elseif (str_contains($name, 'fivem') || str_contains($name, 'astrix') || str_contains($name, 'proxy')) {
        if (str_contains($name, 'internal')) {
            $assignedCat = $categories->first(fn($c) => str_contains(strtolower($c->name), 'internals fivem'));
        } else {
            $assignedCat = $categories->first(fn($c) => str_contains(strtolower($c->name), 'external fivem') || str_contains(strtolower($c->name), 'externals fivem'));
        }
    }
    elseif (str_contains($name, 'valorant') || str_contains($name, 'cs2') || str_contains($name, 'fortnite') || str_contains($name, 'bloodstrike')) {
        $assignedCat = $categories->first(fn($c) => str_contains(strtolower($c->name), 'outros jogos'));
    }
    else {
        $assignedCat = $categories->first(fn($c) => str_contains(strtolower($c->name), 'outros produtos'));
    }
    
    if ($assignedCat) {
        $p->categories()->attach($assignedCat->id);
        $count++;
    }
}

\Illuminate\Support\Facades\Cache::forget('categories.tree');

echo "Concluído! $count produtos categorizados.";
