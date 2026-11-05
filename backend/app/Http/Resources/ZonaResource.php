<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ZonaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'x' => $this->x,
            'y' => $this->y,
            'ancho' => $this->ancho,
            'alto' => $this->alto,
            'mesas' => MesaResource::collection($this->whenLoaded('mesas')),
        ];
    }
}
