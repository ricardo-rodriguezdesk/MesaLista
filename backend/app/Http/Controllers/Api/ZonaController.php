<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ZonaRequest;
use App\Http\Resources\ZonaResource;
use App\Models\Zona;
use Illuminate\Http\JsonResponse;

class ZonaController extends Controller
{
    public function store(ZonaRequest $request): JsonResponse
    {
        $zona = Zona::create($request->validated())->refresh();

        return (new ZonaResource($zona))->response()->setStatusCode(201);
    }

    public function update(ZonaRequest $request, Zona $zona): ZonaResource
    {
        $zona->update($request->validated());

        return new ZonaResource($zona);
    }

    /**
     * Solo se puede eliminar una zona vacía.
     */
    public function destroy(Zona $zona): JsonResponse
    {
        if ($zona->mesas()->exists()) {
            return response()->json([
                'message' => 'La zona tiene mesas. Muévelas a otra zona antes de eliminarla.',
            ], 422);
        }

        $zona->delete();

        return response()->json(['message' => 'Zona eliminada.']);
    }
}
