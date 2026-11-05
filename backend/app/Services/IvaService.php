<?php

namespace App\Services;

/**
 * Cálculo del IVA (RF-04).
 *
 * En México el precio del menú ya incluye IVA, así que el cálculo va
 * "hacia atrás": del precio final se obtiene el precio antes de IVA.
 * Se trabaja en centavos (enteros) para evitar errores de redondeo
 * con números decimales, y el IVA se obtiene por diferencia para que
 * precio_sin_iva + iva siempre sea exactamente el precio final.
 */
class IvaService
{
    public const TASA_GENERAL = 0.16;

    /**
     * @return array{precio_sin_iva: float, iva: float, precio: float}
     */
    public function desglosar(float|string $precio, float|string $tasa = self::TASA_GENERAL, int $cantidad = 1): array
    {
        $centavos = (int) round((float) $precio * 100) * $cantidad;
        $base = (int) round($centavos / (1 + (float) $tasa));

        return [
            'precio_sin_iva' => $base / 100.0,
            'iva' => ($centavos - $base) / 100.0,
            'precio' => $centavos / 100.0,
        ];
    }
}
