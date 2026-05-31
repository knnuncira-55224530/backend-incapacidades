<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $fillable = [
        'nombre', 
        'correo', 
        'usuario', 
        'contrasena', 
        'rol', 
        'token', 
        'sesion_activa', 
        'estado'
    ];
    public $timestamps = true;
}