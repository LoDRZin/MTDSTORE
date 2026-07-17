<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $lowStockCount = \App\Models\Product::whereIn('status', ['active', 'published'])
        ->withCount(['stockItems' => function ($query) {
            $query->where('status', 'available');
        }])
        ->having('stock_items_count', '<', 5)
        ->count(); // if this fails, we need ->get()->count()
    echo "Count: " . $lowStockCount . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
