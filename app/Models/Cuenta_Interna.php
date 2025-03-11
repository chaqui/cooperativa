<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cuenta_Interna extends Model
{
    use HasFactory;

    protected $table = 'cuenta_interna';

    protected $fillable = [
        'ingreso',
        'egreso',
        'descripcion',
    ];
}
