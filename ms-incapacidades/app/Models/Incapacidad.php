<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incapacidad extends Model
{
    protected $table = 'incapacidades';

    protected $fillable = [
        'empleado_id',
        'fecha_inicio',
        'fecha_fin',
        'tipo',
        'diagnostico_general',
        'entidad_medica',
        'observaciones',
        'dias_incapacidad',
        'estado'
    ];
}