<?php

namespace Database\Seeders;

use App\Models\Mesa;
use App\Models\Zona;
use Illuminate\Database\Seeder;

class CroquisSeeder extends Seeder
{
    /**
     * Croquis de ejemplo: salón, terraza y barra.
     */
    public function run(): void
    {
        $salon = Zona::updateOrCreate(
            ['nombre' => 'Salón'],
            ['tipo' => 'salon', 'x' => 20, 'y' => 20, 'ancho' => 600, 'alto' => 400],
        );

        $terraza = Zona::updateOrCreate(
            ['nombre' => 'Terraza'],
            ['tipo' => 'terraza', 'x' => 640, 'y' => 20, 'ancho' => 300, 'alto' => 400],
        );

        $barra = Zona::updateOrCreate(
            ['nombre' => 'Barra'],
            ['tipo' => 'barra', 'x' => 20, 'y' => 440, 'ancho' => 920, 'alto' => 120],
        );

        // Salón: 6 mesas cuadradas en 2 filas
        $numero = 1;
        foreach ([80, 260] as $y) {
            foreach ([60, 240, 420] as $x) {
                $this->mesa($salon, 'M'.$numero++, 4, 'cuadrada', $x, $y, 100, 100);
            }
        }

        // Terraza: 3 mesas redondas
        foreach ([60, 180, 300] as $i => $y) {
            $this->mesa($terraza, 'T'.($i + 1), 2, 'redonda', 750, $y, 80, 80);
        }

        // Barra: 4 lugares
        foreach ([80, 280, 480, 680] as $i => $x) {
            $this->mesa($barra, 'B'.($i + 1), 1, 'barra', $x, 470, 120, 60);
        }
    }

    private function mesa(Zona $zona, string $nombre, int $capacidad, string $forma, int $x, int $y, int $ancho, int $alto): void
    {
        Mesa::updateOrCreate(
            ['nombre' => $nombre],
            [
                'zona_id' => $zona->id,
                'capacidad' => $capacidad,
                'forma' => $forma,
                'x' => $x,
                'y' => $y,
                'ancho' => $ancho,
                'alto' => $alto,
                'estado' => Mesa::LIBRE,
                'activo' => true,
            ],
        );
    }
}
