<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_tasa extends Model
{
    protected $table = 'tipos_tasa';
    protected $fillable = ['nombre'];
    protected $hidden = ['created_at', 'updated_at'];
}
