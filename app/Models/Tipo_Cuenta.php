<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_Cuenta extends Model
{
    protected $table = 'tipo_cuentas';
    protected $fillable = ['nombre'];
    public $timestamps = false;
}
