<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Mesa;
use App\Models\Rol;
use App\Models\Usuario;
use App\Models\Zona;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CroquisTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    private Usuario $mesero;

    private Zona $salon;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->admin = $this->crearUsuario(Rol::ADMINISTRADOR, 'admin');
        $this->mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');
        $this->salon = Zona::create(['nombre' => 'Salón', 'tipo' => 'salon', 'x' => 0, 'y' => 0, 'ancho' => 600, 'alto' => 400]);
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

    private function crearMesa(string $nombre, array $datos = []): Mesa
    {
        return Mesa::create(array_merge([
            'zona_id' => $this->salon->id,
            'nombre' => $nombre,
            'x' => 10,
            'y' => 10,
        ], $datos))->refresh();
    }

    public function test_el_personal_ve_el_croquis_sin_mesas_desactivadas(): void
    {
        $this->crearMesa('M1');
        $this->crearMesa('M2', ['activo' => false]);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/croquis')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Salón')
            ->assertJsonCount(1, 'data.0.mesas')
            ->assertJsonPath('data.0.mesas.0.nombre', 'M1')
            ->assertJsonPath('data.0.mesas.0.estado', Mesa::LIBRE)
            ->assertJsonPath('data.0.mesas.0.cuenta', null);
    }

    public function test_una_mesa_ocupada_muestra_su_cuenta_en_curso(): void
    {
        $mesa = $this->crearMesa('M1', ['estado' => Mesa::OCUPADA]);
        Cuenta::create(['mesa_id' => $mesa->id, 'mesero_id' => $this->mesero->id, 'comensales' => 3, 'total' => 245]);

        $this->actingAs($this->mesero, 'sanctum')
            ->getJson('/api/croquis')
            ->assertOk()
            ->assertJsonPath('data.0.mesas.0.estado', Mesa::OCUPADA)
            ->assertJsonPath('data.0.mesas.0.cuenta.comensales', 3)
            ->assertJsonPath('data.0.mesas.0.cuenta.mesero', 'Mesero1')
            ->assertJsonPath('data.0.mesas.0.cuenta.total', 245);
    }

    public function test_el_administrador_guarda_las_posiciones_del_croquis(): void
    {
        $m1 = $this->crearMesa('M1');
        $m2 = $this->crearMesa('M2');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/croquis', [
                'zonas' => [['id' => $this->salon->id, 'x' => 20, 'y' => 30, 'ancho' => 700, 'alto' => 500]],
                'mesas' => [
                    ['id' => $m1->id, 'x' => 100, 'y' => 120, 'ancho' => 90, 'alto' => 90, 'rotacion' => 45],
                    ['id' => $m2->id, 'x' => 300, 'y' => 120, 'ancho' => 90, 'alto' => 90],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('zonas', ['id' => $this->salon->id, 'x' => 20, 'ancho' => 700]);
        $this->assertDatabaseHas('mesas', ['id' => $m1->id, 'x' => 100, 'y' => 120, 'rotacion' => 45]);
        $this->assertDatabaseHas('mesas', ['id' => $m2->id, 'x' => 300]);
    }

    public function test_si_un_elemento_no_existe_no_se_guarda_nada(): void
    {
        $m1 = $this->crearMesa('M1');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson('/api/croquis', [
                'mesas' => [
                    ['id' => $m1->id, 'x' => 500, 'y' => 500, 'ancho' => 90, 'alto' => 90],
                    ['id' => 999, 'x' => 0, 'y' => 0, 'ancho' => 90, 'alto' => 90],
                ],
            ])
            ->assertStatus(422);

        $this->assertDatabaseHas('mesas', ['id' => $m1->id, 'x' => 10]);
    }

    public function test_un_mesero_no_puede_editar_el_croquis(): void
    {
        $m1 = $this->crearMesa('M1');

        $this->actingAs($this->mesero, 'sanctum')
            ->putJson('/api/croquis', ['mesas' => [['id' => $m1->id, 'x' => 0, 'y' => 0, 'ancho' => 90, 'alto' => 90]]])
            ->assertForbidden();
    }

    public function test_el_administrador_agrega_una_mesa(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/mesas', ['zona_id' => $this->salon->id, 'nombre' => 'M7', 'capacidad' => 6, 'forma' => 'redonda'])
            ->assertCreated()
            ->assertJsonPath('data.nombre', 'M7')
            ->assertJsonPath('data.estado', Mesa::LIBRE)
            ->assertJsonPath('data.activo', true);
    }

    public function test_no_se_repite_el_nombre_de_una_mesa(): void
    {
        $this->crearMesa('M1');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/mesas', ['zona_id' => $this->salon->id, 'nombre' => 'M1'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nombre');
    }

    public function test_no_se_puede_desactivar_una_mesa_ocupada(): void
    {
        $mesa = $this->crearMesa('M1', ['estado' => Mesa::OCUPADA]);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/mesas/{$mesa->id}")
            ->assertStatus(422);

        $this->assertTrue($mesa->fresh()->activo);
    }

    public function test_solo_se_elimina_una_zona_vacia(): void
    {
        $this->crearMesa('M1');
        $terraza = Zona::create(['nombre' => 'Terraza', 'tipo' => 'terraza']);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/zonas/{$this->salon->id}")
            ->assertStatus(422);

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/zonas/{$terraza->id}")
            ->assertOk();

        $this->assertDatabaseMissing('zonas', ['id' => $terraza->id]);
    }
}
