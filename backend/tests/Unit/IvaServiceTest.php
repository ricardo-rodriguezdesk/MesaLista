<?php

namespace Tests\Unit;

use App\Services\IvaService;
use PHPUnit\Framework\TestCase;

class IvaServiceTest extends TestCase
{
    private IvaService $iva;

    protected function setUp(): void
    {
        parent::setUp();

        $this->iva = new IvaService;
    }

    public function test_desglosa_un_precio_exacto(): void
    {
        $this->assertSame(
            ['precio_sin_iva' => 100.0, 'iva' => 16.0, 'precio' => 116.0],
            $this->iva->desglosar(116),
        );
    }

    public function test_redondea_a_centavos(): void
    {
        // 120 / 1.16 = 103.448... → 103.45 y el IVA es la diferencia
        $this->assertSame(
            ['precio_sin_iva' => 103.45, 'iva' => 16.55, 'precio' => 120.0],
            $this->iva->desglosar('120.00'),
        );
    }

    public function test_tasa_cero(): void
    {
        $this->assertSame(
            ['precio_sin_iva' => 45.0, 'iva' => 0.0, 'precio' => 45.0],
            $this->iva->desglosar(45, 0),
        );
    }

    public function test_multiplica_por_la_cantidad(): void
    {
        // 3 × 95 = 285
        $this->assertSame(
            ['precio_sin_iva' => 245.69, 'iva' => 39.31, 'precio' => 285.0],
            $this->iva->desglosar(95, 0.16, 3),
        );
    }

    public function test_precio_sin_iva_mas_iva_siempre_da_el_precio(): void
    {
        // Revisa todos los precios de $0.01 a $500.00
        for ($centavos = 1; $centavos <= 50000; $centavos++) {
            $precio = $centavos / 100;
            $d = $this->iva->desglosar($precio);

            $this->assertSame(
                $centavos,
                (int) round($d['precio_sin_iva'] * 100) + (int) round($d['iva'] * 100),
                "Falla con el precio {$precio}",
            );
        }
    }
}
