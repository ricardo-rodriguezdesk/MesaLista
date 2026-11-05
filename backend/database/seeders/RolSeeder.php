<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Rol::ADMINISTRADOR => 'Acceso total: menú, croquis, empleados y reportes',
            Rol::CAJERO => 'Cobro de cuentas, tickets y corte de caja',
            Rol::MESERO => 'Apertura de mesas y toma de comandas',
            Rol::COCINA => 'Pantalla de pedidos pendientes',
        ];

        foreach ($roles as $nombre => $descripcion) {
            Rol::updateOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
