<?php

use App\Models\Product;
use App\Models\Category;

echo "Iniciando ajuste de categorias...\n";

// 1. Mover Rockstar FiveM para 'Contas e Serviços'
$rockstar = Product::where('name', 'like', '%rockstar%fivem%full%')->first();
$catContas = Category::where('slug', 'contas-e-servicos')->first();

if ($rockstar && $catContas) {
    $rockstar->categories()->sync([$catContas->id]);
    echo "Movido Rockstar FiveM para Contas e Servicos.\n";
} else {
    echo "Aviso: Rockstar ou Categoria de contas não encontrada.\n";
}

// 2. Mover tudo de EXTERNAL FIVEM para CHEAT EXTERNAL FIVEM e deletar EXTERNAL FIVEM
$oldCat = Category::where('slug', 'external-fivem')->first();
$newCat = Category::where('slug', 'cheat-externals-fivem')->first();

if ($oldCat && $newCat) {
    $productsInOld = $oldCat->products()->get();
    foreach ($productsInOld as $prod) {
        $prod->categories()->attach($newCat->id);
        $prod->categories()->detach($oldCat->id);
    }
    echo "Movidos " . count($productsInOld) . " produtos de External para Cheat Externals.\n";
    
    $oldCat->delete();
    echo "Categoria 'External FiveM' deletada.\n";
} else {
    echo "Aviso: Categoria antiga ou nova não encontrada.\n";
}

\Illuminate\Support\Facades\Cache::forget('categories.tree');
echo "Categorias ajustadas!\n";
