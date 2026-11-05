<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Menú de ejemplo. Los precios son finales, con IVA incluido.
     */
    public function run(): void
    {
        $menu = [
            'Entradas' => [
                ['Guacamole con totopos', 120],
                ['Queso fundido con chorizo', 135],
                ['Sopa de tortilla', 85],
            ],
            'Tacos' => [
                ['Tacos al pastor (3 piezas)', 95],
                ['Tacos de bistec (3 piezas)', 105],
                ['Gringa de pastor', 89],
            ],
            'Platos fuertes' => [
                ['Enchiladas verdes', 145],
                ['Arrachera con guarnición', 245],
                ['Pechuga a la plancha', 175],
            ],
            'Bebidas' => [
                ['Agua fresca de horchata', 45],
                ['Refresco', 38],
                ['Cerveza nacional', 55],
                ['Café americano', 35],
            ],
            'Postres' => [
                ['Flan napolitano', 60],
                ['Pastel de chocolate', 75],
            ],
        ];

        $orden = 1;

        foreach ($menu as $nombreCategoria => $productos) {
            $categoria = Categoria::updateOrCreate(
                ['nombre' => $nombreCategoria],
                ['orden' => $orden++, 'activo' => true],
            );

            foreach ($productos as [$nombre, $precio]) {
                $categoria->productos()->updateOrCreate(
                    ['nombre' => $nombre],
                    ['precio' => $precio, 'tasa_iva' => 0.16, 'activo' => true],
                );
            }
        }
    }
}
