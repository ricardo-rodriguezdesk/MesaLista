<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\CuentaDetalle;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Zona;
use App\Services\ComandaService;
use App\Services\TiempoRealService;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TiempoRealTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_aviso_se_publica_en_el_canal_de_supabase(): void
    {
        config([
            'services.supabase.url' => 'https://demo.supabase.co',
            'services.supabase.secret_key' => 'llave-secreta',
            'services.supabase.canal' => 'mesalista',
        ]);
        Http::fake();

        app(TiempoRealService::class)->enviar('mesa.actualizada', ['id' => 3, 'estado' => 'ocupada']);

        Http::assertSent(function (Request $peticion) {
            $mensaje = $peticion['messages'][0];

            return $peticion->url() === 'https://demo.supabase.co/realtime/v1/api/broadcast'
                && $peticion->hasHeader('apikey', 'llave-secreta')
                && $mensaje['topic'] === 'mesalista'
                && $mensaje['event'] === 'mesa.actualizada'
                && $mensaje['payload'] === ['id' => 3, 'estado' => 'ocupada'];
        });
    }

    public function test_si_supabase_falla_la_operacion_no_se_interrumpe(): void
    {
        config(['services.supabase.url' => 'https://demo.supabase.co', 'services.supabase.secret_key' => 'x']);
        Http::fake(['*' => Http::response('Error', 500)]);

        app(TiempoRealService::class)->enviar('mesa.actualizada', ['id' => 1]);

        $this->assertTrue(true);
    }

    public function test_sin_configuracion_no_se_envia_nada(): void
    {
        Http::fake();

        app(TiempoRealService::class)->emitir('mesa.actualizada', ['id' => 1]);

        Http::assertNothingSent();
    }

    public function test_abrir_una_mesa_y_avanzar_un_platillo_avisa_a_las_pantallas(): void
    {
        $this->seed(RolSeeder::class);
        $mesero = Usuario::create([
            'rol_id' => Rol::where('nombre', Rol::MESERO)->value('id'),
            'nombre' => 'Mesero', 'usuario' => 'mesero1', 'pin' => '1234', 'activo' => true,
        ]);
        $mesa = Mesa::create(['zona_id' => Zona::create(['nombre' => 'Salón'])->id, 'nombre' => 'M1'])->refresh();
        $producto = Producto::create([
            'categoria_id' => Categoria::create(['nombre' => 'Tacos'])->id,
            'nombre' => 'Tacos al pastor',
            'precio' => 95,
        ]);

        $avisos = [];
        $this->mock(TiempoRealService::class)
            ->shouldReceive('emitir')
            ->andReturnUsing(function (string $evento, array $datos) use (&$avisos) {
                $avisos[] = [$evento, $datos];
            });

        $comandas = app(ComandaService::class);
        $cuenta = $comandas->abrirCuenta($mesero, $mesa->id, 2);
        $comandas->agregarPlatillos($cuenta, [['producto_id' => $producto->id, 'cantidad' => 1]]);
        $detalle = CuentaDetalle::sole();
        $detalle->update(['estado' => CuentaDetalle::LISTO]);

        $this->assertContains(['mesa.actualizada', ['id' => $mesa->id, 'estado' => Mesa::OCUPADA]], $avisos);
        $this->assertContains(['pedido.nuevo', ['id' => $detalle->id, 'cuenta_id' => $cuenta->id, 'estado' => 'pendiente']], $avisos);
        $this->assertContains(['pedido.actualizado', ['id' => $detalle->id, 'cuenta_id' => $cuenta->id, 'estado' => 'listo']], $avisos);
    }
}
