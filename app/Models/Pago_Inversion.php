<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago_Inversion extends Model
{
    protected $table = 'pagos_inversiones';
    protected $fillable = ['monto', 'fecha', "fecha_pago", "realizado", "inversion_id"];

    public function inversion()
    {
        return $this->belongsTo(Inversion::class, 'inversion');
    }


}
