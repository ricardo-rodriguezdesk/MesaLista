<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\RolController;
use App\Models\Rol;
use Illuminate\Support\Facades\Route;

/*
| Rutas de la API REST de MesaLista (prefijo /api).
| El administrador tiene acceso a todas las rutas protegidas.
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

Route::middleware('auth:sanctum')->group(function () {
    // Todo el personal: consulta del menú
    Route::middleware('rol:'.implode(',', [Rol::CAJERO, Rol::MESERO, Rol::COCINA]))->group(function () {
        Route::get('categorias', [CategoriaController::class, 'index']);
        Route::get('categorias/{categoria}', [CategoriaController::class, 'show']);
    });

    // Solo administrador
    Route::middleware('rol:'.Rol::ADMINISTRADOR)->group(function () {
        Route::get('roles', RolController::class);

        // DELETE desactiva (no borra) para conservar el historial
        Route::apiResource('empleados', EmpleadoController::class)
            ->parameters(['empleados' => 'usuario']);

        Route::apiResource('categorias', CategoriaController::class)
            ->except(['index', 'show']);
    });
});
