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
        'dpi_cliente' // Nuevo campo para DPI del cliente
    ];


    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente', 'dpi');
    }


}
