<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{

    protected $table = 'pagos';
    protected $fillable = ['fecha', 'fecha_pago', 'realizado', 'id_prestamo', 'interes', 'capital', 'saldo', 'monto_pagado', 'penalizacion', 'capital_pagado','id_pago_anterior', 'no_documento', 'tipo_documento', 'fecha_documento', 'recargo', 'interes_pagado','nuevo_saldo'];
    protected $hidden = ['created_at', 'updated_at'];

    public function prestamo()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'id_prestamo');
    }

    public function monto()
    {
        return $this->interes + $this->capital + $this->penalizacion;
    }

    public function saldoFaltante()
    {
       return $this->monto() - $this->monto_pagado;
    }

    public function pagoSiguiente(){
        return $this->where('id_pago_anterior', $this->id)->first();
    }

    public function pagoAnterior(){
        if($this->id_pago_anterior == null){
            return null;
        }
        return $this->where('id', $this->id_pago_anterior)->first();
    }
}
