<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaDetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto' => $this->whenLoaded('producto', fn () => $this->producto->nombre),
            'cantidad' => $this->cantidad,
            'precio_unitario' => (float) $this->precio_unitario,
            'importe' => round((float) $this->precio_unitario * $this->cantidad, 2),
            'notas' => $this->notas,
            'comensal' => $this->comensal,
            'estado' => $this->estado,
            'enviado_en' => $this->enviado_en?->toIso8601String(),
            'listo_en' => $this->listo_en?->toIso8601String(),
        ];
    }
}
