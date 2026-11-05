<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BitacoraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->created_at?->toIso8601String(),
            'usuario' => $this->usuario?->nombre,
            'accion' => $this->accion,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'valores_anteriores' => $this->valores_anteriores,
            'valores_nuevos' => $this->valores_nuevos,
        ];
    }
}
