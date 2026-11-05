<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Producto;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $mesero;

    private Categoria $tacos;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->admin = $this->crearUsuario(Rol::ADMINISTRADOR, 'admin');
        $this->mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');
        $this->tacos = Categoria::create(['nombre' => 'Tacos', 'orden' => 1]);
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

    private function crearProducto(string $nombre, float $precio, ?Categoria $categoria = null, bool $activo = true): Producto
    {
        return Producto::create([
            'categoria_id' => ($categoria ?? $this->tacos)->id,
            'nombre' => $nombre,
            'precio' => $precio,
            'activo' => $activo,
        ]);
    }

    public function test_el_personal_ve_los_productos_con_el_iva_desglosado(): void
    {
        $this->crearProducto('Tacos al pastor', 116);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/productos')
            ->assertOk()
            ->assertJsonPath('data.0.categoria', 'Tacos')
            ->assertJsonPath('data.0.precio', 116)
            ->assertJsonPath('data.0.precio_sin_iva', 100)
            ->assertJsonPath('data.0.iva', 16);
    }

    public function test_el_administrador_da_de_alta_un_producto(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/productos', [
                'categoria_id' => $this->tacos->id,
                'nombre' => 'Tacos de bistec',
                'precio' => 120,
            ])
            ->assertCreated()
            ->assertJsonPath('data.precio_sin_iva', 103.45)
            ->assertJsonPath('data.iva', 16.55)
            ->assertJsonPath('data.tasa_iva', 0.16)
            ->assertJsonPath('data.clave_prod_serv', '90101501')
            ->assertJsonPath('data.activo', true);
    }

    public function test_un_mesero_no_puede_crear_productos(): void
    {
        $this->actingAs($this->mesero, 'sanctum')
            ->postJson('/api/productos', [
                'categoria_id' => $this->tacos->id,
                'nombre' => 'Gringa',
                'precio' => 89,
            ])
            ->assertForbidden();
    }

    public function test_el_precio_debe_ser_valido(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/productos', [
                'categoria_id' => $this->tacos->id,
                'nombre' => 'Gringa',
                'precio' => -5,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('precio');
    }

    public function test_no_se_repite_el_nombre_dentro_de_la_misma_categoria(): void
    {
        $this->crearProducto('Gringa', 89);
        $bebidas = Categoria::create(['nombre' => 'Bebidas', 'orden' => 2]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/productos', ['categoria_id' => $this->tacos->id, 'nombre' => 'Gringa', 'precio' => 89])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');

        // En otra categoría sí se permite
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/productos', ['categoria_id' => $bebidas->id, 'nombre' => 'Gringa', 'precio' => 89])
            ->assertCreated();
    }

    public function test_el_administrador_actualiza_el_precio(): void
    {
        $producto = $this->crearProducto('Tacos al pastor', 95);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/productos/{$producto->id}", ['precio' => 116])
            ->assertOk()
            ->assertJsonPath('data.precio', 116)
            ->assertJsonPath('data.precio_sin_iva', 100)
            ->assertJsonPath('data.nombre', 'Tacos al pastor');
    }

    public function test_eliminar_un_producto_solo_lo_desactiva(): void
    {
        $producto = $this->crearProducto('Tacos al pastor', 95);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/productos/{$producto->id}")
            ->assertOk();

        $this->assertDatabaseHas('productos', ['id' => $producto->id, 'activo' => false]);
    }

    public function test_el_menu_solo_muestra_categorias_y_productos_activos(): void
    {
        $this->crearProducto('Tacos al pastor', 95);
        $this->crearProducto('Taco de temporada', 80, activo: false);

        $postres = Categoria::create(['nombre' => 'Postres', 'orden' => 2, 'activo' => false]);
        $this->crearProducto('Flan', 60, $postres);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/menu')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nombre', 'Tacos')
            ->assertJsonCount(1, 'data.0.productos')
            ->assertJsonPath('data.0.productos.0.nombre', 'Tacos al pastor');
    }

    public function test_se_puede_buscar_un_producto(): void
    {
        $this->crearProducto('Tacos al pastor', 95);
        $this->crearProducto('Gringa de pastor', 89);
        $this->crearProducto('Tacos de bistec', 105);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/productos?buscar=PASTOR')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
