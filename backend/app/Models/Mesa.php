<?php

namespace App\Models;

use App\Observers\MesaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([MesaObserver::class])]
class Mesa extends Model
{
    public const LIBRE = 'libre';
    public const OCUPADA = 'ocupada';
    public const CUENTA_PEDIDA = 'cuenta_pedida';

    protected $table = 'mesas';

    public $timestamps = false;

    protected $fillable = [
        'zona_id',
        'nombre',
        'capacidad',
        'forma',
        'x',
        'y',
        'ancho',
        'alto',
        'rotacion',
        'estado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class);
    }

    public function cuentas(): HasMany
    {
        return $this->hasMany(Cuenta::class);
    }

    /**
     * Cuenta que está en curso en la mesa (abierta o con la cuenta pedida).
     */
    public function cuentaActiva(): HasOne
    {
        return $this->hasOne(Cuenta::class)
            ->whereIn('estado', [Cuenta::ABIERTA, Cuenta::CUENTA_PEDIDA]);
    }
}
