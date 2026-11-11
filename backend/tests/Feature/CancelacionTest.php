<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\CuentaDetalle;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Zona;
use App\Services\ComandaService;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelacionTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $mesero;

    private Usuario $admin;

    private Mesa $mesa;

    private Producto $pastor;

    private Producto $horchata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');
        $this->admin = $this->crearUsuario(Rol::ADMINISTRADOR, 'admin');

        $this->mesa = Mesa::create(['zona_id' => Zona::create(['nombre' => 'Salón'])->id, 'nombre' => 'M1'])->refresh();
        $categoria = Categoria::create(['nombre' => 'Menú']);
        $this->pastor = Producto::create(['categoria_id' => $categoria->id, 'nombre' => 'Tacos al pastor', 'precio' => 95]);
        $this->horchata = Producto::create(['categoria_id' => $categoria->id, 'nombre' => 'Horchata', 'precio' => 45]);
    }

    private function crearUsuario(string $rol, string $usuario): Usuario
    {
        return Usuario::create([
            'rol_id' => Rol::where('nombre', $rol)->value('id'),
            'nombre' => ucfirst($usuario),
            'usuario' => $usuario,
            'pin' => '1234',
            'activo' => true,
        ]);
    }

    /** Cuenta con 2 tacos al pastor ($190) y 1 horchata ($45) = $235 */
    private function cuentaConPlatillos(): Cuenta
    {
        $comandas = app(ComandaService::class);
        $cuenta = $comandas->abrirCuenta($this->mesero, $this->mesa->id, 2);

        return $comandas->agregarPlatillos($cuenta, [
            ['producto_id' => $this->pastor->id, 'cantidad' => 2],
            ['producto_id' => $this->horchata->id, 'cantidad' => 1],
        ]);
    }

    private function platillo(Producto $producto): CuentaDetalle
    {
        return CuentaDetalle::where('producto_id', $producto->id)->sole();
    }

    public function test_el_mesero_cancela_un_platillo_pendiente_y_se_recalcula_el_total(): void
    {
        $this->cuentaConPlatillos();
        $horchata = $this->platillo($this->horchata);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/platillos/{$horchata->id}/cancelar", ['motivo' => 'El cliente cambió de opinión'])
            ->assertOk()
            ->assertJsonPath('data.total', 190)
            ->assertJsonPath('data.platillos.1.estado', 'cancelado');
    }

    public function test_la_cancelacion_queda_en_la_bitacora_con_quien_y_por_que(): void
    {
        $this->cuentaConPlatillos();
        $pastor = $this->platillo($this->pastor);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/platillos/{$pastor->id}/cancelar", ['motivo' => 'Se equivocó de mesa']);

        $registro = Bitacora::where('accion', 'cancelar_platillo')->sole();

        $this->assertSame($this->mesero->id, $registro->usuario_id);
        $this->assertSame('cuenta_detalle', $registro->entidad);
        $this->assertSame($pastor->id, $registro->entidad_id);
        $this->assertSame('Tacos al pastor', $registro->valores_anteriores['producto']);
        $this->assertSame(2, $registro->valores_anteriores['cantidad']);
        $this->assertEquals(190, $registro->valores_anteriores['importe']);
        $this->assertSame('Se equivocó de mesa', $registro->valores_nuevos['motivo']);
    }

    public function test_el_motivo_es_obligatorio(): void
    {
        $this->cuentaConPlatillos();

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/platillos/{$this->platillo($this->pastor)->id}/cancelar", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('motivo');
    }

    public function test_si_la_cocina_ya_lo_esta_preparando_solo_el_administrador_lo_cancela(): void
    {
        $this->cuentaConPlatillos();
        $pastor = $this->platillo($this->pastor);
        $pastor->update(['estado' => CuentaDetalle::PREPARANDO]);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/platillos/{$pastor->id}/cancelar", ['motivo' => 'Ya no lo quiere'])
            ->assertForbidden();

        $this->assertSame(CuentaDetalle::PREPARANDO, $pastor->fresh()->estado);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/platillos/{$pastor->id}/cancelar", ['motivo' => 'Autorizado por gerencia'])
            ->assertOk();

        $this->assertSame(CuentaDetalle::CANCELADO, $pastor->fresh()->estado);
    }

    public function test_un_platillo_no_se_cancela_dos_veces(): void
    {
        $this->cuentaConPlatillos();
        $pastor = $this->platillo($this->pastor);
        $pastor->update(['estado' => CuentaDetalle::CANCELADO]);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/platillos/{$pastor->id}/cancelar", ['motivo' => 'Otra vez'])
            ->assertStatus(422);
    }

    public function test_un_platillo_cancelado_sale_de_la_pantalla_de_cocina(): void
    {
        $this->cuentaConPlatillos();
        $cocina = $this->crearUsuario(Rol::COCINA, 'cocina1');

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/platillos/{$this->platillo($this->pastor)->id}/cancelar", ['motivo' => 'Cancelado']);

        $this->actingAs($cocina, 'sanctum')
            ->getJson('/api/cocina/pedidos')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.producto', 'Horchata');
    }

    public function test_una_cuenta_abierta_por_error_se_cancela_y_libera_la_mesa(): void
    {
        $cuenta = app(ComandaService::class)->abrirCuenta($this->mesero, $this->mesa->id, 2);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/cuentas/{$cuenta->id}/cancelar", ['motivo' => 'Mesa equivocada'])
            ->assertOk()
            ->assertJsonPath('data.estado', Cuenta::CANCELADA);

        $this->assertSame(Mesa::LIBRE, $this->mesa->fresh()->estado);
        $this->assertSame('cancelar_cuenta', Bitacora::sole()->accion);
    }

    public function test_no_se_cancela_una_cuenta_con_platillos_activos(): void
    {
        $cuenta = $this->cuentaConPlatillos();

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/cuentas/{$cuenta->id}/cancelar", ['motivo' => 'Se fueron'])
            ->assertStatus(422);

        $this->assertSame(Mesa::OCUPADA, $this->mesa->fresh()->estado);
    }
}
