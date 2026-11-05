<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    public const EFECTIVO = 'efectivo';
    public const TARJETA = 'tarjeta';
    public const TRANSFERENCIA = 'transferencia';

    protected $table = 'pagos';

    protected $fillable = [
        'ticket_id',
        'cajero_id',
        'metodo',
        'monto',
        'recibido',
        'cambio',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'recibido' => 'decimal:2',
            'cambio' => 'decimal:2',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function cajero(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cajero_id');
    }
}
