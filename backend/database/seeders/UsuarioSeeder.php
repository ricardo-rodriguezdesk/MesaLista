<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    /**
     * Usuarios de prueba. Los PIN son solo para desarrollo.
     */
    public function run(): void
    {
        $roles = Rol::pluck('id', 'nombre');

        $usuarios = [
            [
                'rol' => Rol::ADMINISTRADOR,
                'nombre' => 'Administrador',
                'usuario' => 'admin',
                'pin' => '1111',
                'email' => env('ADMIN_EMAIL'),
                'puesto' => 'Gerente',
            ],
            ['rol' => Rol::CAJERO, 'nombre' => 'Laura Méndez', 'usuario' => 'caja1', 'pin' => '2222', 'puesto' => 'Cajera'],
            ['rol' => Rol::MESERO, 'nombre' => 'Carlos Pérez', 'usuario' => 'mesero1', 'pin' => '3333', 'puesto' => 'Mesero'],
            ['rol' => Rol::MESERO, 'nombre' => 'Ana Torres', 'usuario' => 'mesero2', 'pin' => '4444', 'puesto' => 'Mesera'],
            ['rol' => Rol::COCINA, 'nombre' => 'Miguel Ramírez', 'usuario' => 'cocina1', 'pin' => '5555', 'puesto' => 'Cocinero'],
        ];

        foreach ($usuarios as $datos) {
            $usuario = Usuario::updateOrCreate(
                ['usuario' => $datos['usuario']],
                [
                    'rol_id' => $roles[$datos['rol']],
                    'nombre' => $datos['nombre'],
                    'pin' => $datos['pin'],
                    'email' => $datos['email'] ?? null,
                    'activo' => true,
                ],
            );

            $usuario->empleado()->updateOrCreate([], [
                'puesto' => $datos['puesto'],
                'fecha_ingreso' => now()->startOfYear(),
            ]);
        }
    }
}
