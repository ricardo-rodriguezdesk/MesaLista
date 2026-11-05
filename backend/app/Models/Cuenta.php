<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cuenta extends Model
{
    public const ABIERTA = 'abierta';
    public const CUENTA_PEDIDA = 'cuenta_pedida';
    public const CERRADA = 'cerrada';
    public const CANCELADA = 'cancelada';

    protected $table = 'cuentas';

    public $timestamps = false;

    protected $fillable = [
        'mesa_id',
        'mesero_id',
        'comensales',
        'estado',
        'subtotal',
        'iva',
        'total',
        'abierta_en',
        'cerrada_en',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'iva' => 'decimal:2',
            'total' => 'decimal:2',
            'abierta_en' => 'datetime',
            'cerrada_en' => 'datetime',
        ];
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function mesero(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'mesero_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(CuentaDetalle::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
