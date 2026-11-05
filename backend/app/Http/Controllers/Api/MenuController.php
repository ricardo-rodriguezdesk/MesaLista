<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MenuController extends Controller
{
    /**
     * Menú completo en una sola petición: categorías activas con sus
     * productos activos. Es lo que usa el mesero al tomar la comanda.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $menu = Categoria::activas()
            ->with(['productos' => fn ($consulta) => $consulta->activos()->orderBy('nombre')])
            ->get();

        return CategoriaResource::collection($menu);
    }
}
