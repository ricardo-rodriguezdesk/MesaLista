<?php

namespace App\Http\Middleware;

use App\Models\Rol;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el acceso solo a los roles indicados.
 * Uso en rutas: ->middleware('rol:cajero,mesero')
 * El administrador tiene acceso a todo.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Un usuario desactivado pierde el acceso aunque conserve un token
        if (! $usuario->activo) {
            return response()->json([
                'message' => 'Tu usuario está desactivado. Habla con el administrador.',
            ], 403);
        }

        if ($usuario->tieneRol(Rol::ADMINISTRADOR) || $usuario->tieneRol(...$roles)) {
            return $next($request);
        }

        return response()->json([
            'message' => 'No tienes permiso para realizar esta acción.',
        ], 403);
    }
}
