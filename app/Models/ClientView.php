<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class ClientView extends Model
{
    protected $table = 'view_clients';
    public $timestamps = false;

    protected $fillable = [
        'client_dpi',
        'nombre_completo',
        'telefono',
        'correo',
        'direccion',
        'genero',
        'fecha_nacimiento',
        'codigo_cliente'
    ];
}
