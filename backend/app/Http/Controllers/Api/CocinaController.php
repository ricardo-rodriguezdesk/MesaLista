<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstadoPedidoRequest;
use App\Http\Resources\PedidoCocinaResource;
use App\Models\CuentaDetalle;
use App\Services\CocinaService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CocinaController extends Controller
{
    public function __construct(private CocinaService $cocina)
    {
    }

    /**
     * Platillos pendientes y en preparación, en orden de llegada.
     */
    public function index(): AnonymousResourceCollection
    {
        return PedidoCocinaResource::collection($this->cocina->pendientes());
    }

    /**
     * La cocina marca un platillo como "preparando" o "listo".
     */
    public function actualizar(EstadoPedidoRequest $request, CuentaDetalle $detalle): PedidoCocinaResource
    {
        $detalle = $this->cocina->cambiarEstado($detalle, $request->validated('estado'));

        return new PedidoCocinaResource($detalle->load(['producto', 'cuenta.mesa', 'cuenta.mesero']));
    }

    /**
     * El mesero entrega en la mesa un platillo que ya está listo.
     */
    public function entregar(CuentaDetalle $detalle): PedidoCocinaResource
    {
        $detalle = $this->cocina->cambiarEstado($detalle, CuentaDetalle::ENTREGADO);

        return new PedidoCocinaResource($detalle->load(['producto', 'cuenta.mesa', 'cuenta.mesero']));
    }
}
