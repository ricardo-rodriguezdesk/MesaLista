<?php

namespace Tests\Feature;

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

class CocinaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $mesero;

    private Usuario $cocina;

    private Producto $pastor;

    private Producto $flan;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');
        $this->cocina = $this->crearUsuario(Rol::COCINA, 'cocina1');

        $this->zona = Zona::create(['nombre' => 'Salón']);
        $categoria = Categoria::create(['nombre' => 'Menú']);
        $this->pastor = Producto::create(['categoria_id' => $categoria->id, 'nombre' => 'Tacos al pastor', 'precio' => 95]);
        $this->flan = Producto::create(['categoria_id' => $categoria->id, 'nombre' => 'Flan', 'precio' => 60]);
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

    /** Abre una cuenta en una mesa nueva y le agrega platillos. */
    private function comanda(string $mesa, array $platillos): Cuenta
    {
        $servicio = app(ComandaService::class);
        $mesa = Mesa::create(['zona_id' => $this->zona->id, 'nombre' => $mesa])->refresh();
        $cuenta = $servicio->abrirCuenta($this->mesero, $mesa->id, 2);

        return $servicio->agregarPlatillos($cuenta, $platillos);
    }

    public function test_la_cocina_ve_los_pedidos_en_orden_de_llegada(): void
    {
        $this->comanda('M1', [['producto_id' => $this->pastor->id, 'cantidad' => 3, 'notas' => 'sin cebolla']]);
        $this->travel(5)->minutes();
        $this->comanda('M2', [['producto_id' => $this->flan->id, 'cantidad' => 1]]);

        $this->actingAs($this->cocina, 'sanctum')
            ->getJson('/api/cocina/pedidos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.mesa', 'M1')
            ->assertJsonPath('data.0.producto', 'Tacos al pastor')
            ->assertJsonPath('data.0.cantidad', 3)
            ->assertJsonPath('data.0.notas', 'sin cebolla')
            ->assertJsonPath('data.0.minutos_espera', 5)
            ->assertJsonPath('data.1.mesa', 'M2');
    }

    public function test_un_platillo_listo_sale_de_la_pantalla_de_cocina(): void
    {
        $this->comanda('M1', [['producto_id' => $this->pastor->id, 'cantidad' => 1]]);
        $detalle = CuentaDetalle::sole();

        $this->actingAs($this->cocina, 'sanctum')
            ->patchJson("/api/cocina/pedidos/{$detalle->id}", ['estado' => 'preparando'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'preparando');

        $this->actingAs($this->cocina, 'sanctum')
            ->patchJson("/api/cocina/pedidos/{$detalle->id}", ['estado' => 'listo'])
            ->assertOk()
            ->assertJsonPath('data.estado', 'listo');

        $this->assertNotNull($detalle->fresh()->listo_en);

        $this->actingAs($this->cocina, 'sanctum')
            ->getJson('/api/cocina/pedidos')
            ->assertJsonCount(0, 'data');
    }

    public function test_un_platillo_listo_no_regresa_a_preparando(): void
    {
        $this->comanda('M1', [['producto_id' => $this->pastor->id, 'cantidad' => 1]]);
        $detalle = CuentaDetalle::sole();
        $detalle->update(['estado' => CuentaDetalle::LISTO]);

        $this->actingAs($this->cocina, 'sanctum')
            ->patchJson("/api/cocina/pedidos/{$detalle->id}", ['estado' => 'preparando'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('estado');
    }

    public function test_el_mesero_no_ve_la_pantalla_de_cocina(): void
    {
        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/cocina/pedidos')
            ->assertForbidden();
    }

    public function test_el_mesero_entrega_un_platillo_listo(): void
    {
        $this->comanda('M1', [['producto_id' => $this->pastor->id, 'cantidad' => 1]]);
        $detalle = CuentaDetalle::sole();

        // Todavía no está listo
        $this->actingAs($this->mesero, 'sanctum')
            ->patchJson("/api/platillos/{$detalle->id}/entregar")
            ->assertStatus(422);

        $detalle->update(['estado' => CuentaDetalle::LISTO]);

        $this->actingAs($this->mesero, 'sanctum')
            ->patchJson("/api/platillos/{$detalle->id}/entregar")
            ->assertOk()
            ->assertJsonPath('data.estado', 'entregado');
    }
}
