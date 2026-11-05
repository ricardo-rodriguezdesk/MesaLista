<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Illuminate\Support\defer;

/**
 * Avisos en tiempo real (RF-06, RNF-04) con Supabase Realtime Broadcast.
 *
 * Laravel publica un aviso corto en el canal (por ejemplo "la mesa 3 cambió
 * a ocupada") y las pantallas conectadas vuelven a pedir los datos a la API,
 * con su token. La base de datos nunca se expone al navegador.
 */
class TiempoRealService
{
    /**
     * Publica el aviso después de enviar la respuesta HTTP,
     * para no hacer más lenta la petición del usuario.
     */
    public function emitir(string $evento, array $datos): void
    {
        if (! $this->configurado()) {
            return;
        }

        defer(fn () => $this->enviar($evento, $datos));
    }

    /**
     * Envía el aviso a la API REST de Supabase Realtime.
     * Si Supabase no responde, se registra en el log sin afectar la operación.
     */
    public function enviar(string $evento, array $datos): void
    {
        try {
            Http::withHeaders(['apikey' => config('services.supabase.secret_key')])
                ->timeout(3)
                ->post(rtrim(config('services.supabase.url'), '/').'/realtime/v1/api/broadcast', [
                    'messages' => [[
                        'topic' => config('services.supabase.canal'),
                        'event' => $evento,
                        'payload' => $datos,
                    ]],
                ])
                ->throw();
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el aviso en tiempo real', [
                'evento' => $evento,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function configurado(): bool
    {
        return filled(config('services.supabase.url')) && filled(config('services.supabase.secret_key'));
    }
}
