<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tasa_Interes extends Model
{
    protected $table = 'tasa__interes';
    protected $fillable = ['tasa', 'fecha_inicio', 'fecha_fin'];
    protected $hidden = ['created_at', 'updated_at'];
}
