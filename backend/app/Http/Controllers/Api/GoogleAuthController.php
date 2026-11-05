<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Inicio de sesión del administrador con Google (OAuth 2.0).
 */
class GoogleAuthController extends Controller
{
    /**
     * Envía al usuario a la pantalla de inicio de sesión de Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Google regresa aquí después de iniciar sesión.
     * Si el correo pertenece a un administrador activo, se genera un token
     * y se redirige al frontend con el token en el fragmento de la URL.
     */
    public function callback(): RedirectResponse
    {
        $frontend = rtrim(config('services.frontend.url'), '/');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable) {
            return redirect()->away($frontend.'/login?error=google');
        }

        if (! ($googleUser->user['email_verified'] ?? false)) {
            return redirect()->away($frontend.'/login?error=correo_no_verificado');
        }

        $usuario = Usuario::with('rol')
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $usuario || ! $usuario->activo || ! $usuario->tieneRol(Rol::ADMINISTRADOR)) {
            return redirect()->away($frontend.'/login?error=no_autorizado');
        }

        // La primera vez se vincula la cuenta de Google con el usuario
        if (! $usuario->google_id) {
            $usuario->update(['google_id' => $googleUser->getId()]);
        }

        $token = $usuario->createToken('mesalista-google', [$usuario->rol->nombre])->plainTextToken;

        return redirect()->away($frontend.'/auth/callback#token='.urlencode($token));
    }
}
