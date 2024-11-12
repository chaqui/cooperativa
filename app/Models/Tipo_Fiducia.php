<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tipo_Fiducia extends Model
{
    protected $table = 'tipo_fiducias';
    protected $fillable = [
        'nombre'
    ];

    public function fiducias()
    {
        return $this->hasMany(FIducia::class, 'id_tipo_fiducia');
    }
}
