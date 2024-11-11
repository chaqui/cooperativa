<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    protected $table = 'propiedades';
    protected $fillable = ['Direccion', 'Descripcion', 'Valor_tasacion', 'Valor_comercial', 'tipo_propiedad'];

    public function tipo_propiedad()
    {
        return $this->belongsTo(Tipo_Propiedad::class, 'tipo_propiedad');
    }
}
