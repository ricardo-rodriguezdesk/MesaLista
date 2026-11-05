<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BitacoraTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Producto $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);

        $this->admin = Usuario::create([
            'rol_id' => Rol::where('nombre', Rol::ADMINISTRADOR)->value('id'),
            'nombre' => 'Administrador',
            'usuario' => 'admin',
            'pin' => '1234',
            'activo' => true,
        ]);

        $categoria = Categoria::create(['nombre' => 'Tacos', 'orden' => 1]);
        $this->producto = Producto::create([
            'categoria_id' => $categoria->id,
            'nombre' => 'Tacos al pastor',
            'precio' => 95,
        ]);
    }

    public function test_un_cambio_de_precio_queda_registrado_con_quien_lo_hizo(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/productos/{$this->producto->id}", ['precio' => 105])
            ->assertOk();

        $registro = Bitacora::sole();

        $this->assertSame('cambiar_precio', $registro->accion);
        $this->assertSame('productos', $registro->entidad);
        $this->assertSame($this->producto->id, $registro->entidad_id);
        $this->assertSame($this->admin->id, $registro->usuario_id);
        $this->assertSame(['precio' => '95.00'], $registro->valores_anteriores);
        $this->assertSame(['precio' => '105.00'], $registro->valores_nuevos);
    }

    public function test_un_cambio_sin_precio_no_se_registra(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/productos/{$this->producto->id}", ['descripcion' => 'Con piña'])
            ->assertOk();

        $this->assertSame(0, Bitacora::count());
    }

    public function test_desactivar_un_producto_queda_registrado(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/productos/{$this->producto->id}")
            ->assertOk();

        $this->assertSame('desactivar_producto', Bitacora::sole()->accion);
    }

    public function test_el_administrador_consulta_la_bitacora(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/productos/{$this->producto->id}", ['precio' => 105]);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/bitacora?accion=cambiar_precio')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.usuario', 'Administrador')
            ->assertJsonPath('data.0.valores_nuevos.precio', '105.00');
    }

    public function test_solo_el_administrador_ve_la_bitacora(): void
    {
        $cajero = Usuario::create([
            'rol_id' => Rol::where('nombre', Rol::CAJERO)->value('id'),
            'nombre' => 'Caja',
            'usuario' => 'caja1',
            'pin' => '1234',
            'activo' => true,
        ]);

        $this->actingAs($cajero, 'sanctum')
            ->getJson('/api/bitacora')
            ->assertForbidden();
    }
}
