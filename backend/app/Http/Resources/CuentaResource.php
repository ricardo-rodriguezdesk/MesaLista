<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CuentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mesa' => $this->whenLoaded('mesa', fn () => ['id' => $this->mesa->id, 'nombre' => $this->mesa->nombre]),
            'mesero' => $this->whenLoaded('mesero', fn () => $this->mesero->nombre),
            'comensales' => $this->comensales,
            'estado' => $this->estado,
            'subtotal' => (float) $this->subtotal,
            'iva' => (float) $this->iva,
            'total' => (float) $this->total,
            'abierta_en' => $this->abierta_en?->toIso8601String(),
            'cerrada_en' => $this->cerrada_en?->toIso8601String(),
            'platillos' => CuentaDetalleResource::collection($this->whenLoaded('detalles')),
        ];
    }
}
