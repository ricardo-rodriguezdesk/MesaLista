<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UsuarioResource;
use App\Models\Usuario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Inicio de sesión del personal con usuario y PIN.
     * Devuelve un token de acceso de Sanctum.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::with('rol')
            ->where('usuario', $request->string('usuario'))
            ->first();

        // Mismo mensaje si no existe o si el PIN es incorrecto,
        // para no revelar qué usuarios existen.
        if (! $usuario || ! $usuario->pin || ! Hash::check($request->string('pin'), $usuario->pin)) {
            throw ValidationException::withMessages([
                'usuario' => 'Usuario o PIN incorrectos.',
            ]);
        }

        if (! $usuario->activo) {
            return response()->json([
                'message' => 'Tu usuario está desactivado. Habla con el administrador.',
            ], 403);
        }

        $token = $usuario->createToken('mesalista', [$usuario->rol->nombre]);

        return response()->json([
            'token' => $token->plainTextToken,
            'usuario' => new UsuarioResource($usuario),
        ]);
    }

    /**
     * Datos del usuario autenticado.
     */
    public function me(Request $request): UsuarioResource
    {
        return new UsuarioResource($request->user()->load('rol'));
    }

    /**
     * Cierra la sesión revocando el token actual.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
