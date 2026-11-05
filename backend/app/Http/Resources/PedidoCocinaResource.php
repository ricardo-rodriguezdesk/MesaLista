<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un platillo tal como lo ve la cocina.
 */
class PedidoCocinaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cuenta_id' => $this->cuenta_id,
            'mesa' => $this->cuenta?->mesa?->nombre,
            'mesero' => $this->cuenta?->mesero?->nombre,
            'producto' => $this->producto?->nombre,
            'cantidad' => $this->cantidad,
            'notas' => $this->notas,
            'comensal' => $this->comensal,
            'estado' => $this->estado,
            'enviado_en' => $this->enviado_en?->toIso8601String(),
            'minutos_espera' => (int) $this->enviado_en?->diffInMinutes(now()),
            'listo_en' => $this->listo_en?->toIso8601String(),
        ];
    }
}
