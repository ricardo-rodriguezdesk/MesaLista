<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'rol' => $this->rol?->nombre,
            'activo' => $this->activo,
        ];
    }
}
