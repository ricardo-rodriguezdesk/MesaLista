<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BitacoraResource;
use App\Models\Bitacora;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BitacoraController extends Controller
{
    /**
     * Movimientos más recientes primero, 50 por página.
     * Filtros opcionales: ?accion=cambiar_precio, ?entidad=productos,
     * ?usuario_id=3, ?desde=2026-11-01, ?hasta=2026-11-30
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'desde' => ['nullable', 'date'],
            'hasta' => ['nullable', 'date'],
            'usuario_id' => ['nullable', 'integer'],
        ]);

        $consulta = Bitacora::with('usuario')
            ->latest('created_at')
            ->latest('id');

        foreach (['accion', 'entidad', 'usuario_id'] as $campo) {
            if ($request->filled($campo)) {
                $consulta->where($campo, $request->input($campo));
            }
        }

        if ($request->filled('desde')) {
            $consulta->whereDate('created_at', '>=', $request->date('desde'));
        }

        if ($request->filled('hasta')) {
            $consulta->whereDate('created_at', '<=', $request->date('hasta'));
        }

        return BitacoraResource::collection($consulta->paginate(50));
    }
}
