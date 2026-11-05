<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $mesero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->admin = $this->crearUsuario(Rol::ADMINISTRADOR, 'admin');
        $this->mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');
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

    public function test_el_personal_consulta_las_categorias_en_orden(): void
    {
        Categoria::create(['nombre' => 'Bebidas', 'orden' => 2]);
        Categoria::create(['nombre' => 'Entradas', 'orden' => 1]);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/categorias')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Entradas')
            ->assertJsonPath('data.1.nombre', 'Bebidas');
    }

    public function test_un_mesero_no_puede_crear_categorias(): void
    {
        $this->actingAs($this->mesero, 'sanctum')
            ->postJson('/api/categorias', ['nombre' => 'Postres'])
            ->assertForbidden();
    }

    public function test_una_categoria_nueva_va_al_final_del_menu(): void
    {
        Categoria::create(['nombre' => 'Entradas', 'orden' => 5]);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categorias', ['nombre' => 'Postres'])
            ->assertCreated()
            ->assertJsonPath('data.orden', 6)
            ->assertJsonPath('data.activo', true);
    }

    public function test_no_se_permiten_categorias_con_el_mismo_nombre(): void
    {
        Categoria::create(['nombre' => 'Postres']);

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/categorias', ['nombre' => 'Postres'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_el_administrador_edita_una_categoria(): void
    {
        $categoria = Categoria::create(['nombre' => 'Postre', 'orden' => 3]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/categorias/{$categoria->id}", ['nombre' => 'Postres'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Postres')
            ->assertJsonPath('data.orden', 3);
    }

    public function test_eliminar_una_categoria_solo_la_desactiva(): void
    {
        $categoria = Categoria::create(['nombre' => 'Temporada']);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/categorias/{$categoria->id}")
            ->assertOk();

        $this->assertDatabaseHas('categorias', ['id' => $categoria->id, 'activo' => false]);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/categorias?activo=1')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
