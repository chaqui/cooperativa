<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_Pago extends Model
{
    protected $table = 'tipos_pago';
    protected $fillable = ['nombre'];
    protected $hidden = ['created_at', 'updated_at'];
}
