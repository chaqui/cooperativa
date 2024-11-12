<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_Inversion extends Model
{
    protected $table = 'tipo_inversiones';
    protected $fillable = ['nombre'];
    protected $hidden = ['created_at', 'updated_at'];
}
