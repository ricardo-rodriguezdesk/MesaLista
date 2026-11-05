<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);

        // Ruta de prueba protegida solo para el rol cajero
        Route::middleware(['auth:sanctum', 'rol:cajero'])
            ->get('/api/prueba/caja', fn () => response()->json(['ok' => true]));
    }

    private function crearUsuario(string $rol, string $usuario, bool $activo = true): Usuario
    {
        return Usuario::create([
            'rol_id' => Rol::where('nombre', $rol)->value('id'),
            'nombre' => ucfirst($usuario),
            'usuario' => $usuario,
            'pin' => '1234',
            'activo' => $activo,
        ]);
    }

    public function test_el_personal_inicia_sesion_con_usuario_y_pin(): void
    {
        $this->crearUsuario(Rol::MESERO, 'mesero1');

        $this->postJson('/api/auth/login', ['usuario' => 'mesero1', 'pin' => '1234'])
            ->assertOk()
            ->assertJsonStructure(['token', 'usuario' => ['id', 'nombre', 'rol']])
            ->assertJsonPath('usuario.rol', Rol::MESERO)
            ->assertJsonMissingPath('usuario.pin');
    }

    public function test_un_pin_incorrecto_es_rechazado(): void
    {
        $this->crearUsuario(Rol::MESERO, 'mesero1');

        $this->postJson('/api/auth/login', ['usuario' => 'mesero1', 'pin' => '9999'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario');
    }

    public function test_un_pin_con_letras_no_pasa_la_validacion(): void
    {
        $this->postJson('/api/auth/login', ['usuario' => 'mesero1', 'pin' => 'abcd'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pin');
    }

    public function test_un_usuario_desactivado_no_puede_iniciar_sesion(): void
    {
        $this->crearUsuario(Rol::MESERO, 'mesero1', activo: false);

        $this->postJson('/api/auth/login', ['usuario' => 'mesero1', 'pin' => '1234'])
            ->assertForbidden();
    }

    public function test_sin_token_la_api_responde_401_en_json(): void
    {
        // Sin el encabezado Accept: application/json
        $this->get('/api/auth/me')
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_el_rol_permitido_puede_acceder(): void
    {
        $cajero = $this->crearUsuario(Rol::CAJERO, 'caja1');

        $this->actingAs($cajero, 'sanctum')
            ->getJson('/api/prueba/caja')
            ->assertOk();
    }

    public function test_un_rol_no_permitido_recibe_403(): void
    {
        $mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');

        $this->actingAs($mesero, 'sanctum')
            ->getJson('/api/prueba/caja')
            ->assertForbidden()
            ->assertJson(['message' => 'No tienes permiso para realizar esta acción.']);
    }

    public function test_el_administrador_puede_acceder_a_todo(): void
    {
        $admin = $this->crearUsuario(Rol::ADMINISTRADOR, 'admin');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/prueba/caja')
            ->assertOk();
    }

    public function test_un_usuario_desactivado_pierde_el_acceso_aunque_tenga_token(): void
    {
        $cajero = $this->crearUsuario(Rol::CAJERO, 'caja1', activo: false);

        $this->actingAs($cajero, 'sanctum')
            ->getJson('/api/prueba/caja')
            ->assertForbidden();
    }

    public function test_cerrar_sesion_revoca_el_token(): void
    {
        $this->crearUsuario(Rol::MESERO, 'mesero1');

        $token = $this->postJson('/api/auth/login', ['usuario' => 'mesero1', 'pin' => '1234'])
            ->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
