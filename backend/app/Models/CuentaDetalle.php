<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaDetalle extends Model
{
    public const PENDIENTE = 'pendiente';
    public const PREPARANDO = 'preparando';
    public const LISTO = 'listo';
    public const ENTREGADO = 'entregado';
    public const CANCELADO = 'cancelado';

    protected $table = 'cuenta_detalle';

    public $timestamps = false;

    protected $fillable = [
        'cuenta_id',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'tasa_iva',
        'notas',
        'comensal',
        'estado',
        'enviado_en',
        'listo_en',
    ];

    protected function casts(): array
    {
        return [
            'precio_unitario' => 'decimal:2',
            'tasa_iva' => 'decimal:4',
            'enviado_en' => 'datetime',
            'listo_en' => 'datetime',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
