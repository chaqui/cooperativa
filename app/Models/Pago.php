<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{

    protected $table = 'pagos';
    protected $fillable = ['fecha', 'fecha_pago', 'realizado', 'id_prestamo', 'interes', 'capital', 'saldo', 'monto_pagado', 'penalizacion', 'capital_pagado', 'id_pago_anterior', 'no_documento', 'tipo_documento', 'fecha_documento', 'recargo', 'interes_pagado', 'nuevo_saldo', 'numero_pago_prestamo'];
    // Ocultar el campo si no es necesario en JSON
    protected $hidden = ['created_at', 'updated_at'];

    public function prestamo()
    {
        return $this->belongsTo(Prestamo_Hipotecario::class, 'id_prestamo');
    }

    public function monto()
    {
        return $this->interes + $this->capital + $this->penalizacion;
    }

    public function capitalFaltante()
    {
        return $this->capital - $this->capital_pagado;
    }

    public function saldoFaltante()
    {
        $interesFaltante = max(0, $this->interes - $this->interes_pagado);
        $capitalFaltante = max(0, $this->capitalFaltante());
        $recargoFaltante = max(0, $this->penalizacion - $this->recargo);

        return $interesFaltante + $capitalFaltante + $recargoFaltante;
    }

    public function saldoPagado()
    {
        return $this->interes_pagado + $this->capital_pagado + $this->recargo;
    }

    public function pagoSiguiente()
    {
        return $this->where('id_pago_anterior', $this->id)->first();
    }

    public function pagoAnterior()
    {
        if ($this->id_pago_anterior == null) {
            return null;
        }
        return $this->where('id', $this->id_pago_anterior)->first();
    }

    public function depositos()
    {
        return $this->hasMany(Deposito::class, 'id_pago');
    }

    public static function generarPago($prestamo, $interesMensual, $capitalMensual, $nuevoSaldo, $cuotaPagada, $fecha, $pagoAnterior)
    {
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->interes = $interesMensual;
        $pago->capital = $capitalMensual;
        $pago->saldo = $nuevoSaldo;
        $pago->fecha = $fecha;
        $pago->numero_pago_prestamo = $pagoAnterior ? $pagoAnterior->numero_pago_prestamo + 1 : 1;
        $pago->realizado = $prestamo->existente && $pago->numero_pago_prestamo <= $cuotaPagada;
        $pago->id_pago_anterior = $pagoAnterior ? $pagoAnterior->id : null;

        // Inicializar campos adicionales
        $pago->interes_pagado = 0;
        $pago->capital_pagado = 0;
        $pago->monto_pagado = 0;
        $pago->penalizacion = 0;
        $pago->recargo = 0;
        $pago->fecha_pago = null;
        return $pago;
    }
}
