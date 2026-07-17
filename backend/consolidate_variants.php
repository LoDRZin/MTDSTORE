<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductStockItem;

echo "Iniciando consolidação de variantes...\n";

$consolidations = [
    [
        'parent_id' => 65, // Aspect Menu
        'variants' => [
            ['id' => 61, 'name' => '3 Dias']
        ]
    ],
    [
        'parent_id' => 88, // Dyck Sp00fer 1 Click
        'variants' => [
            ['id' => 84, 'name' => '1 Dia'],
            ['id' => 85, 'name' => '3 Dias']
        ]
    ],
    [
        'parent_id' => 60, // MTD ELYSIUM
        'variants' => [
            ['id' => 57, 'name' => 'Diário']
        ]
    ]
];

foreach ($consolidations as $group) {
    $parent = Product::find($group['parent_id']);
    if (!$parent) {
        echo "Parent {$group['parent_id']} não encontrado.\n";
        continue;
    }

    echo "Consolidando para: {$parent->name}\n";

    // Criar uma variação 'Vitalício' ou 'Mensal' para o produto base atual, se não existir?
    // O usuário quer que o preço base seja a assinatura mais barata ou a mais cara?
    // Pelo que fiz, "A partir de" usa a mais barata.
    // Vamos garantir que a variação padrão exista
    $defaultVariant = $parent->variants()->firstOrCreate(
        ['name' => 'Mensal / Vitalício'], // Ou algo genérico
        ['price' => $parent->price]
    );

    // Mover o estoque do produto pai (se tiver) para a variação padrão
    ProductStockItem::where('product_id', $parent->id)->whereNull('variant_id')->update([
        'variant_id' => $defaultVariant->id
    ]);

    foreach ($group['variants'] as $varData) {
        $oldProduct = Product::find($varData['id']);
        if (!$oldProduct) {
            echo " Produto antigo {$varData['id']} não encontrado.\n";
            continue;
        }

        // Criar variante no pai
        $newVariant = $parent->variants()->create([
            'name' => $varData['name'],
            'price' => $oldProduct->price,
        ]);

        // Mover o estoque
        ProductStockItem::where('product_id', $oldProduct->id)->update([
            'product_id' => $parent->id,
            'variant_id' => $newVariant->id
        ]);

        // Deletar o produto antigo
        $oldProduct->delete();
        echo " Variante {$varData['name']} criada e produto antigo deletado.\n";
    }
}

echo "Consolidação finalizada!\n";
