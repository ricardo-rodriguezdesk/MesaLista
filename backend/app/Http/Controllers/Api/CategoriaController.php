<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoriaRequest;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    /**
     * Categorías en el orden en que se muestran en el menú.
     * Filtro opcional: ?activo=1 o ?activo=0
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Categoria::withCount('productos')
            ->orderBy('orden')
            ->orderBy('nombre');

        if ($request->has('activo')) {
            $consulta->where('activo', $request->boolean('activo'));
        }

        return CategoriaResource::collection($consulta->get());
    }

    public function store(CategoriaRequest $request): JsonResponse
    {
        $datos = $request->validated();

        // Si no se indica el orden, va al final del menú
        $datos['orden'] ??= (Categoria::max('orden') ?? 0) + 1;
        $datos['activo'] ??= true;

        $categoria = Categoria::create($datos)->loadCount('productos');

        return (new CategoriaResource($categoria))->response()->setStatusCode(201);
    }

    public function show(Categoria $categoria): CategoriaResource
    {
        return new CategoriaResource($categoria->loadCount('productos'));
    }

    public function update(CategoriaRequest $request, Categoria $categoria): CategoriaResource
    {
        $categoria->update(array_filter(
            $request->validated(),
            fn ($valor, $campo) => $campo !== 'orden' || $valor !== null,
            ARRAY_FILTER_USE_BOTH,
        ));

        return new CategoriaResource($categoria->loadCount('productos'));
    }

    /**
     * Desactiva la categoría: deja de mostrarse en el menú,
     * pero se conserva con sus productos y su historial.
     */
    public function destroy(Categoria $categoria): JsonResponse
    {
        $categoria->update(['activo' => false]);

        return response()->json(['message' => 'Categoría desactivada.']);
    }
}
