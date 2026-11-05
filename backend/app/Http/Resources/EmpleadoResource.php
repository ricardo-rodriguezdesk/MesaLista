<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un empleado = usuario (acceso y rol) + datos de la tabla empleados.
 */
class EmpleadoResource extends JsonResource
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
            'telefono' => $this->empleado?->telefono,
            'puesto' => $this->empleado?->puesto,
            'fecha_ingreso' => $this->empleado?->fecha_ingreso?->toDateString(),
        ];
    }
}
