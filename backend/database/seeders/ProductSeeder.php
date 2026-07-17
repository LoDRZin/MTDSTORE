<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Astrix Internal', 'slug' => 'astrix-internal', 'description' => 'Acesso premium ao Astrix Internal. Funções completas e segurança garantida.', 'price' => 57.00, 'status' => 'active'],
            ['name' => 'Aspect Internal', 'slug' => 'aspect-internal', 'description' => 'Acesso premium ao Aspect Internal com as melhores ferramentas e suporte 24/7.', 'price' => 73.00, 'status' => 'active'],
            ['name' => 'Mtd Elysium External', 'slug' => 'mtd-elysium-external', 'description' => 'Elysium External. Alta performance e totalmente indetectável.', 'price' => 47.00, 'status' => 'active'],
            ['name' => 'Randolas V3 External', 'slug' => 'randolas-v3-external', 'description' => 'Versão V3 do Randolas External com atualizações recentes.', 'price' => 56.00, 'status' => 'active'],
            ['name' => 'Randolas V4 External', 'slug' => 'randolas-v4-external', 'description' => 'A mais nova e poderosa versão V4 do Randolas External.', 'price' => 76.00, 'status' => 'active'],
            ['name' => 'Lyra External', 'slug' => 'lyra-external', 'description' => 'Ferramenta Lyra External focada em leveza e eficiência.', 'price' => 24.00, 'status' => 'active'],
            ['name' => 'China External', 'slug' => 'china-external', 'description' => 'China External. Importado com exclusividade.', 'price' => 66.00, 'status' => 'active'],
            ['name' => 'Dyck Sp00fer', 'slug' => 'dyck-sp00fer', 'description' => 'Sp00fer robusto para mascarar sua máquina.', 'price' => 54.00, 'status' => 'active'],
            ['name' => 'Sp00fer All Games', 'slug' => 'sp00fer-all-games', 'description' => 'Sp00fer universal compatível com todos os jogos.', 'price' => 12.00, 'status' => 'active'],
            ['name' => 'Sp00fer Fortnite Temp 1 Click', 'slug' => 'sp00fer-fortnite-temp-1-click', 'description' => 'Sp00fer focado para Fortnite, solução temporária de 1 clique.', 'price' => 80.00, 'status' => 'active'],
        ];

        foreach ($products as $product) {
            DB::table('products')->insertOrIgnore(array_merge($product, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
