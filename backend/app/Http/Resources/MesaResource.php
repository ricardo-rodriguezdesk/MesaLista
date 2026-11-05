<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MesaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zona_id' => $this->zona_id,
            'nombre' => $this->nombre,
            'capacidad' => $this->capacidad,
            'forma' => $this->forma,
            'x' => $this->x,
            'y' => $this->y,
            'ancho' => $this->ancho,
            'alto' => $this->alto,
            'rotacion' => $this->rotacion,
            'estado' => $this->estado,
            'activo' => $this->activo,
            // Resumen de la cuenta en curso, para mostrarlo sobre la mesa en el croquis
            'cuenta' => $this->whenLoaded('cuentaActiva', fn () => $this->cuentaActiva ? [
                'id' => $this->cuentaActiva->id,
                'comensales' => $this->cuentaActiva->comensales,
                'mesero' => $this->cuentaActiva->mesero?->nombre,
                'total' => (float) $this->cuentaActiva->total,
                'abierta_en' => $this->cuentaActiva->abierta_en?->toIso8601String(),
            ] : null),
        ];
    }
}
