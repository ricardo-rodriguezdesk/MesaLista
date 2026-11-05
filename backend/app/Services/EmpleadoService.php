<?php

namespace App\Services;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de negocio para dar de alta, editar y desactivar empleados.
 * Un empleado se guarda en dos tablas: usuarios (acceso) y empleados (datos personales).
 */
class EmpleadoService
{
    private const CAMPOS_EMPLEADO = ['telefono', 'puesto', 'fecha_ingreso'];

    public function crear(array $datos): Usuario
    {
        return DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([
                'rol_id' => $this->idDelRol($datos['rol']),
                'nombre' => $datos['nombre'],
                'usuario' => $datos['usuario'],
                'pin' => $datos['pin'],
                'email' => $datos['email'] ?? null,
                'activo' => true,
            ]);

            $usuario->empleado()->create(Arr::only($datos, self::CAMPOS_EMPLEADO));

            return $usuario->load(['rol', 'empleado']);
        });
    }

    public function actualizar(Usuario $usuario, array $datos): Usuario
    {
        return DB::transaction(function () use ($usuario, $datos) {
            $campos = Arr::only($datos, ['nombre', 'usuario', 'email', 'activo']);

            // El PIN solo cambia si se envía uno nuevo
            if (! empty($datos['pin'])) {
                $campos['pin'] = $datos['pin'];
            }

            if (isset($datos['rol'])) {
                $campos['rol_id'] = $this->idDelRol($datos['rol']);
            }

            $usuario->update($campos);

            $datosEmpleado = Arr::only($datos, self::CAMPOS_EMPLEADO);
            if ($datosEmpleado !== []) {
                $usuario->empleado()->updateOrCreate([], $datosEmpleado);
            }

            if (array_key_exists('activo', $datos) && ! $datos['activo']) {
                $usuario->tokens()->delete();
            }

            return $usuario->load(['rol', 'empleado']);
        });
    }

    /**
     * No se borra para conservar su historial (cuentas, cobros, asistencias).
     * Se desactiva y se cierran todas sus sesiones.
     */
    public function desactivar(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            $usuario->update(['activo' => false]);
            $usuario->tokens()->delete();
        });
    }

    private function idDelRol(string $nombre): int
    {
        return Rol::where('nombre', $nombre)->value('id');
    }
}
