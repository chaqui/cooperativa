<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado_Cliente extends Model
{
    protected $table = 'estado_clientes';
    protected $fillable = ['name'];

}