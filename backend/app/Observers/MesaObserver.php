<?php

namespace App\Observers;

use App\Models\Mesa;
use App\Services\TiempoRealService;

/**
 * Avisa a todas las pantallas cuando cambia el estado de una mesa
 * o cuando el administrador modifica el croquis.
 */
class MesaObserver
{
    private const CAMPOS_CROQUIS = ['zona_id', 'nombre', 'capacidad', 'forma', 'x', 'y', 'ancho', 'alto', 'rotacion', 'activo'];

    public function __construct(private TiempoRealService $tiempoReal)
    {
    }

    public function created(Mesa $mesa): void
    {
        $this->tiempoReal->emitir('croquis.actualizado', ['mesa_id' => $mesa->id]);
    }

    public function updated(Mesa $mesa): void
    {
        if ($mesa->wasChanged('estado')) {
            $this->tiempoReal->emitir('mesa.actualizada', ['id' => $mesa->id, 'estado' => $mesa->estado]);
        }

        if ($mesa->wasChanged(self::CAMPOS_CROQUIS)) {
            $this->tiempoReal->emitir('croquis.actualizado', ['mesa_id' => $mesa->id]);
        }
    }
}
