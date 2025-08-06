<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiario extends Model
{
    protected $table = 'beneficiarios';
    protected $fillable = [
        'nombre',
        'parentezco',
        'porcentaje',
        'dpi_cliente',
        'fecha_nacimiento', // Nuevo campo para fecha de nacimiento
    ];


    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente', 'dpi');
    }


}
