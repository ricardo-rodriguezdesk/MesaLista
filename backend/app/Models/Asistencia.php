<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    protected $table = 'asistencias';

    public $timestamps = false;

    protected $fillable = [
        'empleado_id',
        'entrada',
        'salida',
        'horas',
    ];

    protected function casts(): array
    {
        return [
            'entrada' => 'datetime',
            'salida' => 'datetime',
            'horas' => 'decimal:2',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }
}
