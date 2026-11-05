<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmpleadoRequest;
use App\Http\Requests\UpdateEmpleadoRequest;
use App\Http\Resources\EmpleadoResource;
use App\Models\Usuario;
use App\Services\EmpleadoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmpleadoController extends Controller
{
    public function __construct(private EmpleadoService $empleados)
    {
    }

    /**
     * Lista de empleados. Filtro opcional: ?activo=1 o ?activo=0
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Usuario::with(['rol', 'empleado'])->orderBy('nombre');

        if ($request->has('activo')) {
            $consulta->where('activo', $request->boolean('activo'));
        }

        return EmpleadoResource::collection($consulta->get());
    }

    public function store(StoreEmpleadoRequest $request): JsonResponse
    {
        $usuario = $this->empleados->crear($request->validated());

        return (new EmpleadoResource($usuario))->response()->setStatusCode(201);
    }

    public function show(Usuario $usuario): EmpleadoResource
    {
        return new EmpleadoResource($usuario->load(['rol', 'empleado']));
    }

    public function update(UpdateEmpleadoRequest $request, Usuario $usuario): EmpleadoResource|JsonResponse
    {
        // El administrador no puede quitarse a sí mismo el acceso ni el rol
        if ($usuario->is($request->user())) {
            $seDesactiva = $request->has('activo') && ! $request->boolean('activo');
            $cambiaRol = $request->filled('rol') && $request->input('rol') !== $usuario->rol->nombre;

            if ($seDesactiva || $cambiaRol) {
                return response()->json([
                    'message' => 'No puedes desactivarte ni cambiar tu propio rol.',
                ], 422);
            }
        }

        return new EmpleadoResource($this->empleados->actualizar($usuario, $request->validated()));
    }

    /**
     * Desactiva al empleado (no se borra para conservar su historial).
     */
    public function destroy(Request $request, Usuario $usuario): JsonResponse
    {
        if ($usuario->is($request->user())) {
            return response()->json(['message' => 'No puedes desactivar tu propio usuario.'], 422);
        }

        $this->empleados->desactivar($usuario);

        return response()->json(['message' => 'Empleado desactivado.']);
    }
}
