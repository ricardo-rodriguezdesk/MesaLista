<?php

namespace App\Observers;

use App\Models\CuentaDetalle;
use App\Services\TiempoRealService;

/**
 * Avisa a la cocina de pedidos nuevos, y a los meseros cuando un platillo cambia de estado.
 */
class CuentaDetalleObserver
{
    public function __construct(private TiempoRealService $tiempoReal)
    {
    }

    public function created(CuentaDetalle $detalle): void
    {
        $this->tiempoReal->emitir('pedido.nuevo', $this->datos($detalle));
    }

    public function updated(CuentaDetalle $detalle): void
    {
        if ($detalle->wasChanged('estado')) {
            $this->tiempoReal->emitir('pedido.actualizado', $this->datos($detalle));
        }
    }

    private function datos(CuentaDetalle $detalle): array
    {
        return [
            'id' => $detalle->id,
            'cuenta_id' => $detalle->cuenta_id,
            'estado' => $detalle->estado,
        ];
    }
}
