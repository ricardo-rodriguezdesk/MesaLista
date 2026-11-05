<?php

namespace App\Observers;

use App\Models\Producto;
use App\Services\BitacoraService;

/**
 * Registra en la bitácora los cambios de precio y las desactivaciones
 * de productos, sin importar desde dónde se hagan.
 */
class ProductoObserver
{
    public function __construct(private BitacoraService $bitacora)
    {
    }

    public function updated(Producto $producto): void
    {
        if ($producto->wasChanged('precio')) {
            $this->bitacora->registrar(
                'cambiar_precio',
                $producto,
                ['precio' => $producto->getOriginal('precio')],
                ['precio' => $producto->precio],
            );
        }

        if ($producto->wasChanged('activo') && ! $producto->activo) {
            $this->bitacora->registrar(
                'desactivar_producto',
                $producto,
                ['activo' => true],
                ['activo' => false],
            );
        }
    }
}
