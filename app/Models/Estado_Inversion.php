<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado_Inversion extends Model
{
    protected $table = 'estado_inversiones';
    protected $fillable = ['nombre'];

    public function inversiones()
    {
        return $this->hasMany(Inversion::class, 'id_estado');
    }
}
