<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;

class RolController extends Controller
{
    /**
     * Roles disponibles, para el selector del formulario de empleados.
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => Rol::orderBy('id')->get(['id', 'nombre', 'descripcion']),
        ]);
    }
}
