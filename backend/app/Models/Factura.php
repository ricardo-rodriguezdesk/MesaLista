<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    public const PENDIENTE = 'pendiente';
    public const TIMBRADA = 'timbrada';
    public const CANCELADA = 'cancelada';
    public const ERROR = 'error';

    protected $table = 'facturas';

    protected $fillable = [
        'ticket_id',
        'facturapi_id',
        'uuid',
        'rfc',
        'razon_social',
        'regimen_fiscal',
        'codigo_postal',
        'uso_cfdi',
        'email',
        'xml_path',
        'pdf_path',
        'total',
        'estado',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }
}
