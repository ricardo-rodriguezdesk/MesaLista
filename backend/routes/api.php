<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
| Rutas de la API REST de MesaLista (prefijo /api).
*/

Route::prefix('auth')->group(function () {
    // Personal: usuario y PIN. Máximo 5 intentos por minuto.
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    // Administrador: Google OAuth 2.0
    Route::get('google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('google/callback', [GoogleAuthController::class, 'callback']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
