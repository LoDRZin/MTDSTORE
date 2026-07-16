<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    private static array $products = [
        ['name' => 'Windows 11 Pro Key', 'price' => 89.90, 'desc' => 'Chave de ativação original para Windows 11 Professional. Entrega automática após confirmação do pagamento.'],
        ['name' => 'Microsoft Office 2021', 'price' => 149.90, 'desc' => 'Licença perpétua do Microsoft Office 2021. Inclui Word, Excel, PowerPoint e Outlook.'],
        ['name' => 'Adobe Photoshop 2024', 'price' => 299.90, 'desc' => 'Licença anual do Adobe Photoshop 2024. A ferramenta líder mundial em edição de imagens.'],
        ['name' => 'Norton 360 - 1 Ano', 'price' => 79.90, 'desc' => 'Antivírus e segurança completa para até 3 dispositivos por 12 meses.'],
        ['name' => 'Kaspersky Total Security', 'price' => 69.90, 'desc' => 'Proteção total para PC, Mac e smartphone com VPN ilimitada incluída.'],
        ['name' => 'Steam Gift Card R$ 50', 'price' => 52.00, 'desc' => 'Gift Card digital para adicionar saldo na sua conta Steam. Código enviado por e-mail.'],
        ['name' => 'Xbox Game Pass Ultimate 3 Meses', 'price' => 119.90, 'desc' => 'Acesso ilimitado a centenas de jogos no Xbox e PC por 3 meses.'],
        ['name' => 'PlayStation Plus 12 Meses', 'price' => 149.90, 'desc' => 'PlayStation Plus Premium por 12 meses. Jogue online, acesse jogos mensais e mais.'],
        ['name' => 'VPN NordVPN 1 Ano', 'price' => 99.90, 'desc' => 'Privacidade e segurança online com NordVPN por 12 meses em até 6 dispositivos.'],
        ['name' => 'Spotify Premium 3 Meses', 'price' => 29.90, 'desc' => 'Código de resgate para 3 meses de Spotify Premium. Música sem anúncios e offline.'],
    ];

    public function definition(): array
    {
        $product = fake()->randomElement(self::$products);
        $name = $product['name'] . ' ' . fake()->numerify('##');
        
        return [
            'name'        => $name,
            'slug'        => Str::slug($name) . '-' . fake()->unique()->numerify('###'),
            'description' => $product['desc'],
            'price'       => $product['price'],
            'status'      => 'active',
        ];
    }
}
