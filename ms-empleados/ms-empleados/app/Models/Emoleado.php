<?php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $table = 'empleados';
    
    protected $fillable = [
        'nombres',
        'apellidos',
        'documento',
        'correo',
        'telefono',
        'cargo',
        'area',
        'fecha_ingreso',
        'estado'
    ];
    
    public $timestamps = true;
    
    // Mutador para asegurar que fecha_ingreso sea Date
    protected $casts = [
        'fecha_ingreso' => 'date:Y-m-d',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}