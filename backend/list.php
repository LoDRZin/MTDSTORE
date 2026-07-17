<?php
use App\Models\Product;
use App\Models\Category;

echo "--- Produtos ---\n";
foreach (Product::all() as $p) {
    echo $p->id . " | " . $p->name . "\n";
}

echo "--- Categorias ---\n";
foreach (Category::all() as $c) {
    echo $c->id . " | " . $c->name . " | " . $c->slug . "\n";
}
