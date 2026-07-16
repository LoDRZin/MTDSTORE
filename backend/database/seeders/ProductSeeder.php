<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Windows 11 Pro Key', 'slug' => 'windows-11-pro-key', 'description' => 'Chave de ativacao original para Windows 11 Professional. Entrega automatica apos confirmacao do pagamento.', 'price' => 89.90, 'status' => 'active'],
            ['name' => 'Microsoft Office 2021', 'slug' => 'microsoft-office-2021', 'description' => 'Licenca perpetua do Microsoft Office 2021. Inclui Word, Excel, PowerPoint e Outlook.', 'price' => 149.90, 'status' => 'active'],
            ['name' => 'Adobe Photoshop 2024', 'slug' => 'adobe-photoshop-2024', 'description' => 'Licenca anual do Adobe Photoshop 2024. A ferramenta lider mundial em edicao de imagens.', 'price' => 299.90, 'status' => 'active'],
            ['name' => 'Norton 360 - 1 Ano', 'slug' => 'norton-360-1-ano', 'description' => 'Antivirus e seguranca completa para ate 3 dispositivos por 12 meses.', 'price' => 79.90, 'status' => 'active'],
            ['name' => 'Kaspersky Total Security', 'slug' => 'kaspersky-total-security', 'description' => 'Protecao total para PC, Mac e smartphone com VPN ilimitada incluida.', 'price' => 69.90, 'status' => 'active'],
            ['name' => 'Steam Gift Card 50', 'slug' => 'steam-gift-card-50', 'description' => 'Gift Card digital para adicionar saldo na sua conta Steam. Codigo enviado por email.', 'price' => 52.00, 'status' => 'active'],
            ['name' => 'Xbox Game Pass Ultimate 3 Meses', 'slug' => 'xbox-game-pass-ultimate-3-meses', 'description' => 'Acesso ilimitado a centenas de jogos no Xbox e PC por 3 meses.', 'price' => 119.90, 'status' => 'active'],
            ['name' => 'PlayStation Plus 12 Meses', 'slug' => 'playstation-plus-12-meses', 'description' => 'PlayStation Plus Premium por 12 meses. Jogue online e acesse jogos mensais.', 'price' => 149.90, 'status' => 'active'],
            ['name' => 'NordVPN 1 Ano', 'slug' => 'nordvpn-1-ano', 'description' => 'Privacidade e seguranca online com NordVPN por 12 meses em ate 6 dispositivos.', 'price' => 99.90, 'status' => 'active'],
            ['name' => 'Spotify Premium 3 Meses', 'slug' => 'spotify-premium-3-meses', 'description' => 'Codigo de resgate para 3 meses de Spotify Premium. Musica sem anuncios e offline.', 'price' => 29.90, 'status' => 'active'],
        ];

        foreach ($products as $product) {
            DB::table('products')->insertOrIgnore(array_merge($product, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
