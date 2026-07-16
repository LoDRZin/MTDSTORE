<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
        $admin->assignRole('super_admin');

        // Produtos de Teste usando Factory
        \App\Models\Product::factory(5)->create();
    }
}
