<?php

namespace App\Http\Resources;

use App\Services\IvaService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $desglose = app(IvaService::class)->desglosar($this->precio, $this->tasa_iva);

        return [
            'id' => $this->id,
            'categoria_id' => $this->categoria_id,
            'categoria' => $this->whenLoaded('categoria', fn () => $this->categoria->nombre),
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'precio' => $desglose['precio'],
            'precio_sin_iva' => $desglose['precio_sin_iva'],
            'iva' => $desglose['iva'],
            'tasa_iva' => (float) $this->tasa_iva,
            'clave_prod_serv' => $this->clave_prod_serv,
            'clave_unidad' => $this->clave_unidad,
            'activo' => $this->activo,
        ];
    }
}
