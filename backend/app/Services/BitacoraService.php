<?php

namespace App\Services;

use App\Models\Bitacora;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Registro de auditoría (RF-15): quién hizo un cambio sensible, cuándo,
 * y cuáles eran los valores antes y después.
 */
class BitacoraService
{
    public function registrar(
        string $accion,
        Model $entidad,
        ?array $antes = null,
        ?array $despues = null,
        ?int $usuarioId = null,
    ): Bitacora {
        return Bitacora::create([
            'usuario_id' => $usuarioId ?? Auth::id(),
            'accion' => $accion,
            'entidad' => $entidad->getTable(),
            'entidad_id' => $entidad->getKey(),
            'valores_anteriores' => $antes,
            'valores_nuevos' => $despues,
        ]);
    }
}
