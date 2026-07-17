<?php

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

echo "Iniciando migração de variantes e recategorização...\n";

// Suffixes that identify a variant time
$suffixes = ['Lifetime', 'Mensal', 'Trimestral', 'Semanal', 'Diário', 'Diario', 'Anual', 'Quinzenal'];

$products = Product::all();

$groups = [];

foreach ($products as $p) {
    $name = trim($p->name);
    $baseName = $name;
    $variantName = 'Padrão';
    
    foreach ($suffixes as $suffix) {
        if (preg_match('/(.*)\s+' . preg_quote($suffix, '/') . '$/i', $name, $matches)) {
            $baseName = trim($matches[1]);
            $variantName = $suffix;
            break;
        }
    }
    
    if (!isset($groups[$baseName])) {
        $groups[$baseName] = [];
    }
    $groups[$baseName][] = [
        'product' => $p,
        'variantName' => ucfirst(strtolower($variantName)) == 'Diario' ? 'Diário' : ucfirst(strtolower($variantName)),
    ];
}

$catExternal = Category::where('slug', 'external-fivem')->orWhere('name', 'EXTERNAL FIVEM')->first();
$catInternal = Category::where('slug', 'cheat-internals-fivem')->orWhere('name', 'CHEAT INTERNALS FIVEM')->first();

DB::beginTransaction();

try {
    foreach ($groups as $baseName => $items) {
        // Se só tem 1 item e o nome da variante é Padrão, não precisamos fazer merge.
        // MAS vamos categorizá-lo corretamente.
        
        // Vamos sempre eleger o primeiro item como o "Pai"
        $parentItem = $items[0];
        // Tenta achar o Lifetime para ser o pai, ou o Mensal
        foreach ($items as $item) {
            if ($item['variantName'] == 'Lifetime') {
                $parentItem = $item;
                break;
            }
        }
        
        $parentProduct = $parentItem['product'];
        $oldParentName = $parentProduct->name;
        
        // Atualiza o nome do pai para o Base Name
        $parentProduct->name = $baseName;
        // Gera um novo slug baseado no baseName
        $parentProduct->slug = \Illuminate\Support\Str::slug($baseName . '-' . uniqid()); 
        
        // Ajusta categorias se for FiveM
        $isFiveM = stripos($baseName, 'fivem') !== false || stripos($parentProduct->description, 'fivem') !== false;
        $isDyck = stripos($baseName, 'dyck') !== false;
        
        if ($isFiveM) {
            $parentProduct->categories()->detach();
            if ($isDyck && $catExternal) {
                $parentProduct->categories()->attach($catExternal->id);
            } elseif (!$isDyck && $catInternal) {
                $parentProduct->categories()->attach($catInternal->id);
            }
        }
        
        $parentProduct->save();
        
        // Agora, para CADA item (incluindo o pai), criamos uma variação dentro do pai
        // SOMENTE SE houver mais de 1 item no grupo, ou se o item já tinha um sufixo
        if (count($items) > 1 || $items[0]['variantName'] !== 'Padrão') {
            foreach ($items as $item) {
                $prod = $item['product'];
                
                // Cria a variação
                $variant = ProductVariant::create([
                    'product_id' => $parentProduct->id,
                    'name' => $item['variantName'],
                    'price' => $prod->price,
                ]);
                
                // Transfere o estoque do $prod para a $variant no $parentProduct
                DB::table('product_stock_items')
                    ->where('product_id', $prod->id)
                    ->update([
                        'product_id' => $parentProduct->id,
                        'variant_id' => $variant->id
                    ]);
                    
                // Se o $prod não for o $parentProduct, deletamos o $prod
                if ($prod->id !== $parentProduct->id) {
                    echo "Mesclando e deletando: {$prod->name} -> Variação '{$item['variantName']}' de '{$baseName}'\n";
                    $prod->delete();
                } else {
                    echo "Pai convertido: {$oldParentName} -> '{$baseName}' com Variação '{$item['variantName']}'\n";
                }
            }
        } else {
            echo "Mantido individual: {$parentProduct->name}\n";
        }
    }
    
    DB::commit();
    echo "Migração concluída com sucesso!\n";
    \Illuminate\Support\Facades\Cache::forget('categories.tree');
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "Erro: " . $e->getMessage() . "\n";
}
