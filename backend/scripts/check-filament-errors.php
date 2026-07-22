<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Verificar todos os Resources
$resources = glob(app_path('Filament/Admin/Resources/**/*.php'));
$resources = array_merge($resources, glob(app_path('Filament/Admin/Resources/**/**/*.php')));
foreach ($resources as $resource) {
    if (!str_ends_with($resource, '.php')) continue;
    try {
        require_once $resource;
    } catch (Error $e) {
        echo "❌ ERROR: " . $e->getMessage() . " in " . $resource . "\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . " in " . $resource . "\n";
    }
}

// Verificar todos os Widgets
$widgets = glob(app_path('Filament/Admin/Widgets/*.php'));
foreach ($widgets as $widget) {
    try {
        require_once $widget;
    } catch (Error $e) {
        echo "❌ ERROR: " . $e->getMessage() . " in " . $widget . "\n";
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . " in " . $widget . "\n";
    }
}

echo "✅ Check complete!\n";
