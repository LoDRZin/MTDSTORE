<?php
use App\Models\Product;
use App\Models\Category;

$rockstar = Product::find(103);
$catContas = Category::find(10); // CONTAS E SERVIÇOS

if ($rockstar && $catContas) {
    $rockstar->categories()->sync([$catContas->id]);
    echo "Movido Rockstar FiveM para Contas e Servicos.\n";
} else {
    echo "Produto ou categoria não encontrados.\n";
}
\Illuminate\Support\Facades\Cache::forget('categories.tree');
