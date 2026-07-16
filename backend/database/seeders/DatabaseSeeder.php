<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Criação de usuário Admin (se não existir)
        $admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@mtdstore.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('senha123'),
            ]
        );

        // Associa role super_admin se existir (Filament Shield)
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
        $admin->assignRole($role);

        // Criação de Categorias Reais
        $catMinecraft = \App\Models\Category::firstOrCreate(
            ['slug' => 'minecraft'],
            [
                'name' => 'Minecraft',
                'description' => 'Contas e itens de Minecraft',
                'image_url' => 'https://images.unsplash.com/photo-1607513746994-51f730a44832?q=80&w=600&auto=format&fit=crop',
            ]
        );

        $catRoblox = \App\Models\Category::firstOrCreate(
            ['slug' => 'roblox'],
            [
                'name' => 'Roblox',
                'description' => 'Robux e contas de Roblox',
                'image_url' => 'https://images.unsplash.com/photo-1614294149010-950b698f72c0?q=80&w=600&auto=format&fit=crop',
            ]
        );

        $this->createProduct(
            'minecraft-full-acesso',
            'Minecraft Full Acesso',
            'Conta Minecraft Original Full Acesso, mude email, senha e nick na hora.',
            79.90,
            'https://images.unsplash.com/photo-1627856013091-fed6e4e30025?q=80&w=600&auto=format&fit=crop',
            $catMinecraft->id,
            15
        );

        $this->createProduct(
            'minecraft-capa-optifine',
            'Capa Optifine Minecraft',
            'Capa exclusiva da Optifine transferível para o seu nick original.',
            35.00,
            'https://images.unsplash.com/photo-1552820728-8b83bb6b773f?q=80&w=600&auto=format&fit=crop',
            $catMinecraft->id,
            50
        );

        $this->createProduct(
            'roblox-1000-robux',
            '1.000 Robux',
            'Recarga de 1.000 Robux direto na sua conta, via Gamepass.',
            55.90,
            'https://images.unsplash.com/photo-1593640408182-31c70c8268f5?q=80&w=600&auto=format&fit=crop',
            $catRoblox->id,
            200
        );

        $this->createProduct(
            'roblox-conta-blox-fruits',
            'Conta Blox Fruits Level Max',
            'Conta de Roblox nível máximo no Blox Fruits com frutas míticas.',
            120.00,
            'https://images.unsplash.com/photo-1605901309584-818e25960b8f?q=80&w=600&auto=format&fit=crop',
            $catRoblox->id,
            5
        );
    }

    private function createProduct($slug, $name, $desc, $price, $image, $catId, $stockCount)
    {
        $prod = \App\Models\Product::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'description' => $desc,
                'price' => $price,
                'image_url' => $image,
                'status' => 'active',
            ]
        );
        
        $prod->categories()->syncWithoutDetaching([$catId]);
        
        if ($prod->stockItems()->count() === 0) {
            for ($i = 0; $i < $stockCount; $i++) {
                $prod->stockItems()->create([
                    'value' => 'mocked-key-' . Str::random(10),
                    'status' => 'available',
                    'batch_id' => Str::uuid(),
                ]);
            }
        }
    }
}
