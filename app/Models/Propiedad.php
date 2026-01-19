<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    protected $table = 'propiedades';
    protected $fillable = ['Direccion', 'Descripcion', 'Valor_tasacion', 'Valor_comercial', 'path_documentacion', 'tipo_propiedad', 'dpi_cliente'];

    public function cliente()
    {
        return $this->belongsTo(Client::class, 'dpi_cliente');
    }

    public function prestamos()
    {
        return $this->hasMany(Prestamo_Hipotecario::class, 'propiedad_id');
    }
}
