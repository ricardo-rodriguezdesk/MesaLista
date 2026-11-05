<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zona extends Model
{
    protected $table = 'zonas';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'tipo',
        'x',
        'y',
        'ancho',
        'alto',
    ];

    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class);
    }
}
