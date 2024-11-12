<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_Tasa_Interes extends Model
{
    protected $table = 'tipo_tasa_interes';
    protected $fillable = ['nombre', 'valor', 'descripcion'];
}
