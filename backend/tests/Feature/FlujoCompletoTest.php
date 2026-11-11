<?php

namespace Tests\Feature;

use App\Models\Cuenta;
use App\Models\Mesa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prueba funcional del flujo principal del restaurante, de punta a punta,
 * usando solo la API (como lo hará el frontend) y los datos de los seeders:
 *
 * mesero inicia sesión → abre mesa → envía comanda → cocina la prepara →
 * mesero la entrega → el cliente pide la cuenta.
 */
class FlujoCompletoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    /** Inicia sesión con PIN y devuelve el token. */
    private function login(string $usuario, string $pin): string
    {
        return $this->postJson('/api/auth/login', ['usuario' => $usuario, 'pin' => $pin])
            ->assertOk()
            ->json('token');
    }

    /** Hace la petición como el usuario dueño del token. */
    private function como(string $token): static
    {
        // Olvida al usuario de la petición anterior para usar el token nuevo
        $this->app['auth']->forgetGuards();

        return $this->withToken($token);
    }

    public function test_de_la_mesa_a_la_cocina_y_de_regreso(): void
    {
        $mesero = $this->login('mesero1', '3333');
        $cocina = $this->login('cocina1', '5555');

        // 1. El mesero ve el croquis y elige una mesa libre
        $mesas = collect($this->como($mesero)->getJson('/api/croquis')->assertOk()->json('data'))
            ->flatMap(fn ($zona) => $zona['mesas']);
        $mesa = $mesas->firstWhere('estado', Mesa::LIBRE);

        // 2. Consulta el menú
        $productos = collect($this->como($mesero)->getJson('/api/menu')->assertOk()->json('data'))
            ->flatMap(fn ($categoria) => $categoria['productos']);
        $pastor = $productos->firstWhere('nombre', 'Tacos al pastor (3 piezas)');
        $horchata = $productos->firstWhere('nombre', 'Agua fresca de horchata');

        // 3. Abre la mesa para 3 personas
        $cuentaId = $this->como($mesero)
            ->postJson('/api/cuentas', ['mesa_id' => $mesa['id'], 'comensales' => 3])
            ->assertCreated()
            ->json('data.id');

        // 4. Envía la comanda: 2 órdenes de pastor sin cebolla y una horchata
        $this->como($mesero)
            ->postJson("/api/cuentas/{$cuentaId}/platillos", ['platillos' => [
                ['producto_id' => $pastor['id'], 'cantidad' => 2, 'notas' => 'sin cebolla'],
                ['producto_id' => $horchata['id'], 'cantidad' => 1],
            ]])
            ->assertOk()
            ->assertJsonPath('data.total', 2 * 95 + 45);

        // 5. En el croquis la mesa aparece ocupada con su cuenta
        $mesaEnCroquis = collect($this->como($mesero)->getJson('/api/croquis')->json('data'))
            ->flatMap(fn ($zona) => $zona['mesas'])
            ->firstWhere('id', $mesa['id']);
        $this->assertSame(Mesa::OCUPADA, $mesaEnCroquis['estado']);
        $this->assertSame(235, $mesaEnCroquis['cuenta']['total']);

        // 6. La cocina recibe los pedidos con sus notas
        $pedidos = $this->como($cocina)->getJson('/api/cocina/pedidos')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.notas', 'sin cebolla')
            ->json('data');

        // 7. La cocina los prepara y los marca como listos
        foreach ($pedidos as $pedido) {
            $this->como($cocina)->patchJson("/api/cocina/pedidos/{$pedido['id']}", ['estado' => 'preparando'])->assertOk();
            $this->como($cocina)->patchJson("/api/cocina/pedidos/{$pedido['id']}", ['estado' => 'listo'])->assertOk();
        }
        $this->como($cocina)->getJson('/api/cocina/pedidos')->assertJsonCount(0, 'data');

        // 8. El mesero los entrega en la mesa
        foreach ($pedidos as $pedido) {
            $this->como($mesero)->patchJson("/api/platillos/{$pedido['id']}/entregar")->assertOk();
        }

        // 9. El cliente pide la cuenta
        $this->como($mesero)
            ->postJson("/api/cuentas/{$cuentaId}/pedir-cuenta")
            ->assertOk()
            ->assertJsonPath('data.estado', Cuenta::CUENTA_PEDIDA)
            ->assertJsonPath('data.subtotal', 202.58)
            ->assertJsonPath('data.iva', 32.42)
            ->assertJsonPath('data.total', 235);

        $this->assertSame(Mesa::CUENTA_PEDIDA, Mesa::find($mesa['id'])->estado);

        // 10. Y la caja la ve en las cuentas en curso
        $caja = $this->login('caja1', '2222');
        $this->como($caja)->getJson('/api/cuentas')
            ->assertOk()
            ->assertJsonPath('data.0.id', $cuentaId)
            ->assertJsonPath('data.0.estado', Cuenta::CUENTA_PEDIDA);
    }
}
