<?php

namespace App\Services;

use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Toma de comandas (RF-07): abrir una mesa, agregar platillos y pedir la cuenta.
 * Cada operación corre en una transacción y bloquea la fila que modifica,
 * para que dos meseros no abran la misma mesa al mismo tiempo.
 */
class ComandaService
{
    public function __construct(private IvaService $iva)
    {
    }

    public function abrirCuenta(Usuario $mesero, int $mesaId, int $comensales): Cuenta
    {
        return DB::transaction(function () use ($mesero, $mesaId, $comensales) {
            $mesa = Mesa::whereKey($mesaId)->lockForUpdate()->firstOrFail();

            if (! $mesa->activo) {
                $this->error('mesa_id', 'La mesa no está disponible.');
            }

            if ($mesa->estado !== Mesa::LIBRE) {
                $this->error('mesa_id', 'La mesa ya tiene una cuenta abierta.');
            }

            $cuenta = Cuenta::create([
                'mesa_id' => $mesa->id,
                'mesero_id' => $mesero->id,
                'comensales' => $comensales,
                'estado' => Cuenta::ABIERTA,
                'abierta_en' => now(),
            ]);

            $mesa->update(['estado' => Mesa::OCUPADA]);

            return $cuenta;
        });
    }

    /**
     * Agrega una comanda (uno o varios platillos) a la cuenta.
     * El precio y la tasa de IVA se copian del producto en ese momento.
     *
     * @param  array<int, array{producto_id: int, cantidad: int, notas?: ?string, comensal?: ?int}>  $platillos
     */
    public function agregarPlatillos(Cuenta $cuenta, array $platillos): Cuenta
    {
        return DB::transaction(function () use ($cuenta, $platillos) {
            $cuenta = Cuenta::whereKey($cuenta->id)->lockForUpdate()->firstOrFail();

            if (! in_array($cuenta->estado, [Cuenta::ABIERTA, Cuenta::CUENTA_PEDIDA], true)) {
                $this->error('cuenta', 'La cuenta ya está cerrada.');
            }

            $productos = Producto::whereIn('id', array_column($platillos, 'producto_id'))->get()->keyBy('id');

            foreach ($platillos as $i => $platillo) {
                $producto = $productos[$platillo['producto_id']];

                if (! $producto->activo) {
                    $this->error("platillos.$i.producto_id", "«{$producto->nombre}» ya no está disponible.");
                }

                $cuenta->detalles()->create([
                    'producto_id' => $producto->id,
                    'cantidad' => $platillo['cantidad'],
                    'precio_unitario' => $producto->precio,
                    'tasa_iva' => $producto->tasa_iva,
                    'notas' => $platillo['notas'] ?? null,
                    'comensal' => $platillo['comensal'] ?? null,
                    'estado' => CuentaDetalle::PENDIENTE,
                    'enviado_en' => now(),
                ]);
            }

            // Si ya habían pedido la cuenta y piden algo más, la cuenta se vuelve a abrir
            if ($cuenta->estado === Cuenta::CUENTA_PEDIDA) {
                $cuenta->update(['estado' => Cuenta::ABIERTA]);
                $cuenta->mesa()->update(['estado' => Mesa::OCUPADA]);
            }

            return $this->recalcular($cuenta);
        });
    }

    public function pedirCuenta(Cuenta $cuenta): Cuenta
    {
        return DB::transaction(function () use ($cuenta) {
            $cuenta = Cuenta::whereKey($cuenta->id)->lockForUpdate()->firstOrFail();

            if ($cuenta->estado !== Cuenta::ABIERTA) {
                $this->error('cuenta', 'La cuenta no está abierta.');
            }

            if (! $cuenta->detalles()->where('estado', '!=', CuentaDetalle::CANCELADO)->exists()) {
                $this->error('cuenta', 'La cuenta no tiene platillos.');
            }

            $cuenta->update(['estado' => Cuenta::CUENTA_PEDIDA]);
            $cuenta->mesa()->update(['estado' => Mesa::CUENTA_PEDIDA]);

            return $cuenta;
        });
    }

    /**
     * Recalcula subtotal, IVA y total con los platillos no cancelados.
     * Se suma en centavos para no acumular errores de redondeo.
     */
    public function recalcular(Cuenta $cuenta): Cuenta
    {
        $subtotal = $iva = $total = 0;

        $detalles = $cuenta->detalles()->where('estado', '!=', CuentaDetalle::CANCELADO)->get();

        foreach ($detalles as $detalle) {
            $d = $this->iva->desglosar($detalle->precio_unitario, $detalle->tasa_iva, $detalle->cantidad);

            $subtotal += (int) round($d['precio_sin_iva'] * 100);
            $iva += (int) round($d['iva'] * 100);
            $total += (int) round($d['precio'] * 100);
        }

        $cuenta->update([
            'subtotal' => $subtotal / 100,
            'iva' => $iva / 100,
            'total' => $total / 100,
        ]);

        return $cuenta;
    }

    private function error(string $campo, string $mensaje): never
    {
        throw ValidationException::withMessages([$campo => $mensaje]);
    }
}
