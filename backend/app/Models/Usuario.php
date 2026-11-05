<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'rol_id',
        'nombre',
        'usuario',
        'pin',
        'email',
        'google_id',
        'activo',
    ];

    protected $hidden = [
        'pin',
        'google_id',
    ];

    protected function casts(): array
    {
        return [
            'pin' => 'hashed',
            'activo' => 'boolean',
        ];
    }

    /**
     * El personal inicia sesión con PIN en lugar de contraseña.
     */
    public function getAuthPasswordName(): string
    {
        return 'pin';
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class);
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class);
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }
}
