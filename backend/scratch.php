<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Product::where('name', 'like', '%Minecraft%')->first();
if ($p) {
    echo "Product: {$p->name}\n";
    $stock = App\Models\ProductStockItem::where('product_id', $p->id)->count();
    $avail = App\Models\ProductStockItem::where('product_id', $p->id)->where('status', 'available')->count();
    echo "Total Stock: {$stock}\n";
    echo "Available Stock: {$avail}\n";
} else {
    echo "Minecraft not found\n";
}
