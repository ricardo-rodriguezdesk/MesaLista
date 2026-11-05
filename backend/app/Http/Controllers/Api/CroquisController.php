<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuardarCroquisRequest;
use App\Http\Resources\ZonaResource;
use App\Services\CroquisService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CroquisController extends Controller
{
    public function __construct(private CroquisService $croquis)
    {
    }

    /**
     * Croquis completo: zonas con sus mesas activas, el estado de cada mesa
     * y el resumen de la cuenta en curso.
     */
    public function index(): AnonymousResourceCollection
    {
        return ZonaResource::collection($this->croquis->obtener());
    }

    /**
     * Guarda las posiciones del editor del croquis (zonas y mesas) en una sola petición.
     */
    public function guardar(GuardarCroquisRequest $request): AnonymousResourceCollection
    {
        $this->croquis->guardarPosiciones($request->validated());

        return ZonaResource::collection($this->croquis->obtener());
    }
}
