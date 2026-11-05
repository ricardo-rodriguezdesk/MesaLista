<?php

namespace App\Services;

use App\Models\Mesa;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Croquis del restaurante (RF-05 y RF-06).
 */
class CroquisService
{
    public function __construct(private TiempoRealService $tiempoReal)
    {
    }

    /**
     * Zonas con sus mesas activas y, si están ocupadas, la cuenta en curso.
     */
    public function obtener(): Collection
    {
        return Zona::query()
            ->with([
                'mesas' => fn ($consulta) => $consulta->where('activo', true)->orderBy('nombre'),
                'mesas.cuentaActiva.mesero',
            ])
            ->orderBy('id')
            ->get();
    }

    /**
     * Guarda en una sola operación las posiciones del editor del croquis.
     * Si algo falla, no se guarda ningún cambio.
     */
    public function guardarPosiciones(array $datos): void
    {
        DB::transaction(function () use ($datos) {
            foreach ($datos['zonas'] ?? [] as $zona) {
                Zona::whereKey($zona['id'])->update(Arr::only($zona, ['x', 'y', 'ancho', 'alto']));
            }

            foreach ($datos['mesas'] ?? [] as $mesa) {
                Mesa::whereKey($mesa['id'])->update(Arr::only($mesa, ['zona_id', 'x', 'y', 'ancho', 'alto', 'rotacion']));
            }
        });

        $this->tiempoReal->emitir('croquis.actualizado', ['origen' => 'editor']);
    }
}
