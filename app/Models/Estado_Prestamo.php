<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado_Prestamo extends Model
{
    protected $table = 'estado_prestamos';
    protected $fillable = ['nombre'];
}
