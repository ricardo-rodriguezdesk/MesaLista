<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BitacoraController;
use App\Http\Controllers\Api\CategoriaController;
use App\Http\Controllers\Api\CroquisController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MesaController;
use App\Http\Controllers\Api\ProductoController;
use App\Http\Controllers\Api\RolController;
use App\Http\Controllers\Api\ZonaController;
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
    // Todo el personal: consulta del menú y del croquis
    Route::middleware('rol:'.implode(',', [Rol::CAJERO, Rol::MESERO, Rol::COCINA]))->group(function () {
        Route::get('menu', MenuController::class);
        Route::get('croquis', [CroquisController::class, 'index']);

        Route::get('categorias', [CategoriaController::class, 'index']);
        Route::get('categorias/{categoria}', [CategoriaController::class, 'show']);

        Route::get('productos', [ProductoController::class, 'index']);
        Route::get('productos/{producto}', [ProductoController::class, 'show']);
    });

    // Solo administrador
    Route::middleware('rol:'.Rol::ADMINISTRADOR)->group(function () {
        Route::get('roles', RolController::class);
        Route::get('bitacora', BitacoraController::class);

        // DELETE desactiva (no borra) para conservar el historial
        Route::apiResource('empleados', EmpleadoController::class)
            ->parameters(['empleados' => 'usuario']);

        Route::apiResource('categorias', CategoriaController::class)
            ->except(['index', 'show']);

        Route::apiResource('productos', ProductoController::class)
            ->except(['index', 'show']);

        // Editor del croquis
        Route::put('croquis', [CroquisController::class, 'guardar']);
        Route::apiResource('zonas', ZonaController::class)->only(['store', 'update', 'destroy']);
        Route::apiResource('mesas', MesaController::class);
    });
});
