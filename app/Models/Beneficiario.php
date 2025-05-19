<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiario extends Model
{
    protected $table = 'beneficiarios';
    protected $fillable = [
        'nombre',
        'parentezco',
        'porcentaje',
        'id_inversion'
    ];
    public function inversion()
    {
        return $this->belongsTo(Inversion::class, 'id_inversion');
    }

}
