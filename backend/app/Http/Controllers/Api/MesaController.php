<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MesaRequest;
use App\Http\Resources\MesaResource;
use App\Models\Mesa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MesaController extends Controller
{
    /**
     * Todas las mesas, incluidas las desactivadas (para el editor del administrador).
     */
    public function index(): AnonymousResourceCollection
    {
        return MesaResource::collection(Mesa::orderBy('nombre')->get());
    }

    public function store(MesaRequest $request): JsonResponse
    {
        $mesa = Mesa::create($request->validated())->refresh();

        return (new MesaResource($mesa))->response()->setStatusCode(201);
    }

    public function show(Mesa $mesa): MesaResource
    {
        return new MesaResource($mesa->load('cuentaActiva.mesero'));
    }

    public function update(MesaRequest $request, Mesa $mesa): MesaResource|JsonResponse
    {
        $datos = $request->validated();

        if (array_key_exists('activo', $datos) && ! $datos['activo'] && $mesa->estado !== Mesa::LIBRE) {
            return $this->mesaOcupada();
        }

        $mesa->update($datos);

        return new MesaResource($mesa);
    }

    /**
     * Desactiva la mesa (se quita del croquis, pero conserva sus cuentas anteriores).
     */
    public function destroy(Mesa $mesa): JsonResponse
    {
        if ($mesa->estado !== Mesa::LIBRE) {
            return $this->mesaOcupada();
        }

        $mesa->update(['activo' => false]);

        return response()->json(['message' => 'Mesa desactivada.']);
    }

    private function mesaOcupada(): JsonResponse
    {
        return response()->json([
            'message' => 'La mesa tiene una cuenta abierta. Ciérrala antes de desactivarla.',
        ], 422);
    }
}
