<?php

namespace App\Services;

use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Pantalla de cocina (RF-08): pedidos pendientes en orden de llegada
 * y avance de cada platillo: pendiente → preparando → listo → entregado.
 */
class CocinaService
{
    /** Cambios de estado permitidos para cada estado actual. */
    private const TRANSICIONES = [
        CuentaDetalle::PENDIENTE => [CuentaDetalle::PREPARANDO, CuentaDetalle::LISTO],
        CuentaDetalle::PREPARANDO => [CuentaDetalle::LISTO],
        CuentaDetalle::LISTO => [CuentaDetalle::ENTREGADO],
    ];

    public function pendientes(): Collection
    {
        return CuentaDetalle::query()
            ->with(['producto', 'cuenta.mesa', 'cuenta.mesero'])
            ->whereIn('estado', [CuentaDetalle::PENDIENTE, CuentaDetalle::PREPARANDO])
            ->whereHas('cuenta', fn ($consulta) => $consulta->whereIn('estado', [Cuenta::ABIERTA, Cuenta::CUENTA_PEDIDA]))
            ->orderBy('enviado_en')
            ->orderBy('id')
            ->get();
    }

    public function cambiarEstado(CuentaDetalle $detalle, string $nuevo): CuentaDetalle
    {
        $permitidos = self::TRANSICIONES[$detalle->estado] ?? [];

        if (! in_array($nuevo, $permitidos, true)) {
            throw ValidationException::withMessages([
                'estado' => "Un platillo «{$detalle->estado}» no puede pasar a «{$nuevo}».",
            ]);
        }

        $detalle->update([
            'estado' => $nuevo,
            'listo_en' => $nuevo === CuentaDetalle::LISTO ? now() : $detalle->listo_en,
        ]);

        return $detalle;
    }
}
