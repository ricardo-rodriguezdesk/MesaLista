<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
| Rutas de la API REST de MesaLista (prefijo /api).
*/

Route::prefix('auth')->group(function () {
    // Máximo 5 intentos por minuto para evitar adivinar PINs
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
