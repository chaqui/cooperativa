<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{

    protected $table = 'pagos';
    protected $fillable = ['fecha', 'monto', 'fecha_pago', 'realizado', 'id_prestamo'];
    protected $hidden = ['created_at', 'updated_at'];

    public function prestamo()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'prestamo');
    }
}
