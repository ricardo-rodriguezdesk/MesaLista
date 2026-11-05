<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Cuenta;
use App\Models\Mesa;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Zona;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComandaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $mesero;

    private Mesa $mesa;

    private Producto $pastor;

    private Producto $horchata;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');

        $zona = Zona::create(['nombre' => 'Salón']);
        $this->mesa = Mesa::create(['zona_id' => $zona->id, 'nombre' => 'M1'])->refresh();

        $categoria = Categoria::create(['nombre' => 'Tacos']);
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

    private function abrirCuenta(): int
    {
        return $this->actingAs($this->mesero, 'sanctum')
            ->postJson('/api/cuentas', ['mesa_id' => $this->mesa->id, 'comensales' => 3])
            ->json('data.id');
    }

    private function agregar(int $cuentaId, array $platillos)
    {
        return $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/cuentas/{$cuentaId}/platillos", ['platillos' => $platillos]);
    }

    public function test_el_mesero_abre_una_cuenta_y_la_mesa_queda_ocupada(): void
    {
        $this->actingAs($this->mesero, 'sanctum')
            ->postJson('/api/cuentas', ['mesa_id' => $this->mesa->id, 'comensales' => 3])
            ->assertCreated()
            ->assertJsonPath('data.mesa.nombre', 'M1')
            ->assertJsonPath('data.mesero', 'Mesero1')
            ->assertJsonPath('data.comensales', 3)
            ->assertJsonPath('data.estado', Cuenta::ABIERTA)
            ->assertJsonPath('data.total', 0);

        $this->assertSame(Mesa::OCUPADA, $this->mesa->fresh()->estado);
    }

    public function test_no_se_abren_dos_cuentas_en_la_misma_mesa(): void
    {
        $this->abrirCuenta();

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson('/api/cuentas', ['mesa_id' => $this->mesa->id, 'comensales' => 2])
            ->assertStatus(422)
            ->assertJsonValidationErrors('mesa_id');

        $this->assertSame(1, Cuenta::count());
    }

    public function test_no_se_abre_una_cuenta_en_una_mesa_desactivada(): void
    {
        $this->mesa->update(['activo' => false]);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson('/api/cuentas', ['mesa_id' => $this->mesa->id, 'comensales' => 2])
            ->assertStatus(422);
    }

    public function test_agregar_platillos_con_notas_calcula_los_totales(): void
    {
        $cuentaId = $this->abrirCuenta();

        // 2 × 95 + 1 × 45 = 235
        $this->agregar($cuentaId, [
            ['producto_id' => $this->pastor->id, 'cantidad' => 2, 'notas' => 'sin cebolla'],
            ['producto_id' => $this->horchata->id, 'cantidad' => 1],
        ])
            ->assertOk()
            ->assertJsonCount(2, 'data.platillos')
            ->assertJsonPath('data.platillos.0.producto', 'Tacos al pastor')
            ->assertJsonPath('data.platillos.0.notas', 'sin cebolla')
            ->assertJsonPath('data.platillos.0.importe', 190)
            ->assertJsonPath('data.platillos.0.estado', 'pendiente')
            ->assertJsonPath('data.total', 235)
            ->assertJsonPath('data.subtotal', 202.58)
            ->assertJsonPath('data.iva', 32.42);
    }

    public function test_el_precio_queda_fijo_aunque_cambie_en_el_menu(): void
    {
        $cuentaId = $this->abrirCuenta();
        $this->agregar($cuentaId, [['producto_id' => $this->pastor->id, 'cantidad' => 1]]);

        $this->pastor->update(['precio' => 120]);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson("/api/cuentas/{$cuentaId}")
            ->assertOk()
            ->assertJsonPath('data.platillos.0.precio_unitario', 95)
            ->assertJsonPath('data.total', 95);
    }

    public function test_no_se_puede_pedir_un_producto_desactivado(): void
    {
        $cuentaId = $this->abrirCuenta();
        $this->horchata->update(['activo' => false]);

        $this->agregar($cuentaId, [['producto_id' => $this->horchata->id, 'cantidad' => 1]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('platillos.0.producto_id');
    }

    public function test_pedir_la_cuenta_cambia_el_estado_de_la_mesa(): void
    {
        $cuentaId = $this->abrirCuenta();
        $this->agregar($cuentaId, [['producto_id' => $this->pastor->id, 'cantidad' => 1]]);

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/cuentas/{$cuentaId}/pedir-cuenta")
            ->assertOk()
            ->assertJsonPath('data.estado', Cuenta::CUENTA_PEDIDA);

        $this->assertSame(Mesa::CUENTA_PEDIDA, $this->mesa->fresh()->estado);
    }

    public function test_no_se_pide_la_cuenta_sin_platillos(): void
    {
        $cuentaId = $this->abrirCuenta();

        $this->actingAs($this->mesero, 'sanctum')
            ->postJson("/api/cuentas/{$cuentaId}/pedir-cuenta")
            ->assertStatus(422);
    }

    public function test_si_piden_algo_mas_la_cuenta_se_vuelve_a_abrir(): void
    {
        $cuentaId = $this->abrirCuenta();
        $this->agregar($cuentaId, [['producto_id' => $this->pastor->id, 'cantidad' => 1]]);
        $this->actingAs($this->mesero, 'sanctum')->postJson("/api/cuentas/{$cuentaId}/pedir-cuenta");

        $this->agregar($cuentaId, [['producto_id' => $this->horchata->id, 'cantidad' => 1]])
            ->assertOk()
            ->assertJsonPath('data.estado', Cuenta::ABIERTA)
            ->assertJsonPath('data.total', 140);

        $this->assertSame(Mesa::OCUPADA, $this->mesa->fresh()->estado);
    }

    public function test_la_caja_ve_las_cuentas_en_curso(): void
    {
        $this->abrirCuenta();
        $cajero = $this->crearUsuario(Rol::CAJERO, 'caja1');

        $this->actingAs($cajero, 'sanctum')
            ->getJson('/api/cuentas')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.mesa.nombre', 'M1');
    }

    public function test_la_cocina_no_puede_abrir_cuentas(): void
    {
        $cocina = $this->crearUsuario(Rol::COCINA, 'cocina1');

        $this->actingAs($cocina, 'sanctum')
            ->postJson('/api/cuentas', ['mesa_id' => $this->mesa->id, 'comensales' => 2])
            ->assertForbidden();
    }
}
