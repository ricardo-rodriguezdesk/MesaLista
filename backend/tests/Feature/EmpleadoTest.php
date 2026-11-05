<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Usuario;
use Database\Seeders\RolSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpleadoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolSeeder::class);
        $this->admin = $this->crearUsuario(Rol::ADMINISTRADOR, 'admin', 'admin@mesalista.test');
    }

    private function crearUsuario(string $rol, string $usuario, ?string $email = null): Usuario
    {
        return Usuario::create([
            'rol_id' => Rol::where('nombre', $rol)->value('id'),
            'nombre' => ucfirst($usuario),
            'usuario' => $usuario,
            'pin' => '1234',
            'email' => $email,
            'activo' => true,
        ]);
    }

    private function datosEmpleado(array $cambios = []): array
    {
        return array_merge([
            'nombre' => 'Laura Méndez',
            'usuario' => 'laura',
            'pin' => '2468',
            'rol' => Rol::CAJERO,
            'telefono' => '4611234567',
            'puesto' => 'Cajera',
            'fecha_ingreso' => '2026-01-15',
        ], $cambios);
    }

    public function test_el_administrador_lista_los_empleados(): void
    {
        $this->crearUsuario(Rol::MESERO, 'mesero1');

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/empleados')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_un_mesero_no_puede_administrar_empleados(): void
    {
        $mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');

        $this->actingAs($mesero, 'sanctum')
            ->getJson('/api/empleados')
            ->assertForbidden();
    }

    public function test_el_administrador_da_de_alta_un_empleado_que_puede_iniciar_sesion(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/empleados', $this->datosEmpleado())
            ->assertCreated()
            ->assertJsonPath('data.rol', Rol::CAJERO)
            ->assertJsonPath('data.puesto', 'Cajera')
            ->assertJsonPath('data.fecha_ingreso', '2026-01-15')
            ->assertJsonMissingPath('data.pin');

        $this->postJson('/api/auth/login', ['usuario' => 'laura', 'pin' => '2468'])
            ->assertOk();
    }

    public function test_no_se_permiten_usuarios_duplicados(): void
    {
        $this->crearUsuario(Rol::MESERO, 'laura');

        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/empleados', $this->datosEmpleado())
            ->assertStatus(422)
            ->assertJsonValidationErrors('usuario');
    }

    public function test_un_administrador_nuevo_requiere_correo(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/empleados', $this->datosEmpleado(['rol' => Rol::ADMINISTRADOR]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_actualizar_sin_pin_conserva_el_pin_anterior(): void
    {
        $mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/empleados/{$mesero->id}", ['nombre' => 'Carlos Pérez', 'puesto' => 'Mesero'])
            ->assertOk()
            ->assertJsonPath('data.nombre', 'Carlos Pérez')
            ->assertJsonPath('data.puesto', 'Mesero');

        $this->postJson('/api/auth/login', ['usuario' => 'mesero1', 'pin' => '1234'])
            ->assertOk();
    }

    public function test_desactivar_un_empleado_cierra_sus_sesiones(): void
    {
        $mesero = $this->crearUsuario(Rol::MESERO, 'mesero1');
        $mesero->createToken('mesalista');

        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/empleados/{$mesero->id}")
            ->assertOk();

        $this->assertFalse($mesero->fresh()->activo);
        $this->assertSame(0, $mesero->tokens()->count());
    }

    public function test_el_administrador_no_puede_desactivarse_a_si_mismo(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/empleados/{$this->admin->id}")
            ->assertStatus(422);

        $this->assertTrue($this->admin->fresh()->activo);
    }

    public function test_los_roles_se_pueden_consultar(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/roles')
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }
}
