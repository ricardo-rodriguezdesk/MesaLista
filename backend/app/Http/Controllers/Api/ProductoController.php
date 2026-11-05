<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductoRequest;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductoController extends Controller
{
    /**
     * Filtros opcionales: ?categoria_id=1, ?activo=1, ?buscar=taco
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $consulta = Producto::with('categoria')->orderBy('nombre');

        if ($request->filled('categoria_id')) {
            $consulta->where('categoria_id', $request->integer('categoria_id'));
        }

        if ($request->has('activo')) {
            $consulta->where('activo', $request->boolean('activo'));
        }

        if ($request->filled('buscar')) {
            $consulta->whereRaw('LOWER(nombre) LIKE ?', ['%'.mb_strtolower($request->string('buscar')).'%']);
        }

        return ProductoResource::collection($consulta->get());
    }

    public function store(ProductoRequest $request): JsonResponse
    {
        // refresh() trae los valores por omisión de la base de datos (tasa de IVA, claves del SAT)
        $producto = Producto::create($request->validated())->refresh()->load('categoria');

        return (new ProductoResource($producto))->response()->setStatusCode(201);
    }

    public function show(Producto $producto): ProductoResource
    {
        return new ProductoResource($producto->load('categoria'));
    }

    public function update(ProductoRequest $request, Producto $producto): ProductoResource
    {
        $producto->update($request->validated());

        return new ProductoResource($producto->load('categoria'));
    }

    /**
     * Desactiva el producto: deja de aparecer en el menú,
     * pero se conserva en las cuentas y tickets anteriores.
     */
    public function destroy(Producto $producto): JsonResponse
    {
        $producto->update(['activo' => false]);

        return response()->json(['message' => 'Producto desactivado.']);
    }
}
