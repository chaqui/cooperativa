<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago_Inversion extends Model
{
    protected $table = 'pago_inversions';
    protected $fillable = [
        'monto',
        'fecha',
        "fecha_pago",
        "realizado",
        "inversion_id",
        "no_boleta",
        'montoInteres',
        'montoISR'
    ];

    public function inversion()
    {
        return $this->belongsTo(Inversion::class, 'inversion_id');
    }
}
