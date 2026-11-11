<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AbrirCuentaRequest;
use App\Http\Requests\AgregarPlatillosRequest;
use App\Http\Requests\CancelarRequest;
use App\Http\Resources\CuentaResource;
use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use App\Services\ComandaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CuentaController extends Controller
{
    public function __construct(private ComandaService $comandas)
    {
    }

    /**
     * Cuentas en curso (abiertas o con la cuenta pedida).
     */
    public function index(): AnonymousResourceCollection
    {
        $cuentas = Cuenta::with(['mesa', 'mesero'])
            ->whereIn('estado', [Cuenta::ABIERTA, Cuenta::CUENTA_PEDIDA])
            ->orderBy('abierta_en')
            ->get();

        return CuentaResource::collection($cuentas);
    }

    /**
     * Abre una cuenta en una mesa libre. La mesa pasa a "ocupada".
     */
    public function store(AbrirCuentaRequest $request): JsonResponse
    {
        $cuenta = $this->comandas->abrirCuenta(
            $request->user(),
            $request->integer('mesa_id'),
            $request->integer('comensales'),
        );

        return (new CuentaResource($this->cargar($cuenta)))->response()->setStatusCode(201);
    }

    /**
     * Detalle de la cuenta con sus platillos y totales.
     */
    public function show(Cuenta $cuenta): CuentaResource
    {
        return new CuentaResource($this->cargar($cuenta));
    }

    /**
     * Agrega platillos (con notas, por ejemplo "sin cebolla") y los envía a cocina.
     */
    public function agregarPlatillos(AgregarPlatillosRequest $request, Cuenta $cuenta): CuentaResource
    {
        $cuenta = $this->comandas->agregarPlatillos($cuenta, $request->validated('platillos'));

        return new CuentaResource($this->cargar($cuenta));
    }

    /**
     * El cliente pide la cuenta: la mesa pasa a "cuenta pedida".
     */
    public function pedirCuenta(Cuenta $cuenta): CuentaResource
    {
        return new CuentaResource($this->cargar($this->comandas->pedirCuenta($cuenta)));
    }

    /**
     * Cancela un platillo con su motivo. Queda registrado en la bitácora.
     */
    public function cancelarPlatillo(CancelarRequest $request, CuentaDetalle $detalle): CuentaResource
    {
        $cuenta = $this->comandas->cancelarPlatillo($detalle, $request->user(), $request->validated('motivo'));

        return new CuentaResource($this->cargar($cuenta));
    }

    /**
     * Cancela una cuenta abierta por error (sin platillos activos) y libera la mesa.
     */
    public function cancelarCuenta(CancelarRequest $request, Cuenta $cuenta): CuentaResource
    {
        $cuenta = $this->comandas->cancelarCuenta($cuenta, $request->user(), $request->validated('motivo'));

        return new CuentaResource($this->cargar($cuenta));
    }

    private function cargar(Cuenta $cuenta): Cuenta
    {
        return $cuenta->fresh(['mesa', 'mesero', 'detalles.producto']);
    }
}
