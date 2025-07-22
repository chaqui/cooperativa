<?php

namespace App\Models;

use App\Traits\Loggable;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{

    use Loggable;

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
        return max(0, $this->capital - $this->capital_pagado);
    }

    public function penalizacionFaltante()
    {
        return max(0, $this->penalizacion - $this->recargo);
    }

    public function interesFaltante()
    {
        $fechaAnterior = $this->pagoAnterior()?->fecha ?? $this->prestamo?->fecha_inicio;
        $fechaAnteriorCarbon = \Carbon\Carbon::parse($fechaAnterior);

        $diasTranscuridos = $fechaAnteriorCarbon->diffInDays(now());

        $diasMesAnterior = $fechaAnteriorCarbon->daysInMonth;
        if ($diasTranscuridos > $diasMesAnterior) {
            $interesCalculado = $this->interes;
        } else {
            $interesCalculado = $this->interes * ($diasTranscuridos / $diasMesAnterior);
        }

        return max(0, $interesCalculado - $this->interes_pagado);
    }



    public function saldoFaltante()
    {
        $interesFaltante = max(0, $this->interes - $this->interes_pagado);

        return $interesFaltante + $this->capitalFaltante() + $this->penalizacionFaltante();
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

    /**
     * Método reutilizable para inicializar un pago
     *
     * @param Prestamo_Hipotecario $prestamo
     * @param float $interes
     * @param float $capital
     * @param float $saldo
     * @param string $fecha
     * @param int|null $numeroPago
     * @param bool $realizado
     * @param Pago|null $pagoAnterior
     * @return Pago
     */
    private static function inicializarPago($prestamo, $interes, $capital, $saldo, $fecha, $numeroPago = null, $realizado = false, $pagoAnterior = null): Pago
    {
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->interes = round($interes, 2); // Redondear a 2 decimales
        $pago->capital = round($capital, 2); // Redondear a 2 decimales
        $pago->saldo = round($saldo, 2); // Redondear a 2 decimales
        $pago->fecha = $fecha;
        $pago->numero_pago_prestamo = $numeroPago ?? ($pagoAnterior ? $pagoAnterior->numero_pago_prestamo + 1 : 1);
        $pago->realizado = $realizado;
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

    /**
     * Genera un pago válido
     *
     * @param Prestamo_Hipotecario $prestamo
     * @param float $interesMensual
     * @param float $capitalMensual
     * @param float $nuevoSaldo
     * @param int $cuotaPagada
     * @param string $fecha
     * @param Pago|null $pagoAnterior
     * @return Pago
     */
    public static function generarPago($prestamo, $interesMensual, $capitalMensual, $nuevoSaldo, $cuotaPagada, $fecha, $pagoAnterior): Pago
    {

        return self::inicializarPago(
            $prestamo,
            $interesMensual,
            $capitalMensual,
            $nuevoSaldo,
            $fecha,
            null,
            false,
            $pagoAnterior
        );
    }

    /**
     * Genera un pago inválido
     *
     * @param Prestamo_Hipotecario $prestamo
     * @param float $interesAcumulado
     * @param string $fecha
     * @return Pago
     */
    public static function generarPagoInvalido($prestamo, $interesAcumulado, $fecha): Pago
    {
        return self::inicializarPago(
            $prestamo,
            $interesAcumulado,
            0, // No se amortiza capital en un pago inválido
            $prestamo->monto, // El saldo se mantiene igual
            $fecha,
            0, // Número de pago inicial
            false // No realizado
        );
    }
}
