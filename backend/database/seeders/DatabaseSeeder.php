<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Los roles son necesarios en todos los entornos
        $this->call(RolSeeder::class);

        // Datos de ejemplo: solo en desarrollo y pruebas
        if (! app()->isProduction()) {
            $this->call([
                UsuarioSeeder::class,
                MenuSeeder::class,
                CroquisSeeder::class,
            ]);
        }
    }
}
