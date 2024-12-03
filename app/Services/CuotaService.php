<?php

namespace App\Services;

use App\Models\Prestamo_Hipotecario;
use App\Models\Inversion;
use App\Models\Pago;
use App\Models\Pago_Inversion;

class CuotaService
{
    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario)
    {
        $monto = $prestamoHipotecario->monto;
        $interes = $prestamoHipotecario->interes;
        $plazo = $prestamoHipotecario->plazo;
        $tipoTaza = $prestamoHipotecario->tipoTaza->valor;
        $tipoPlazo = $prestamoHipotecario->tipoPlazo->nombre;

        $cuota = $this->calcularCuota($monto, $interes, $plazo, $tipoTaza, $tipoPlazo);

        $this->generarCuotas($prestamoHipotecario, $cuota);
    }

    private function generarCuotas(Prestamo_Hipotecario $prestamoHipotecario, $cuota)
    {

        $plazo = $prestamoHipotecario->plazo;
        $fecha = $prestamoHipotecario->fecha_inicio;

        for ($i = 0; $i < $plazo; $i++) {
            $fecha = date('Y-m-d', strtotime($fecha . ' + 1 month'));

            $pago = new Pago();
            $pago->monto = round($cuota, 2);
            $pago->fecha_pago = $fecha;
            $pago->realizado = false;
            $pago->id_prestamo = $prestamoHipotecario->id;
            $pago->save();
        }
    }

    public function calcularCuotaInversion(Inversion $inversion)
    {
        $monto = $inversion->monto;
        $interes = $inversion->interes;
        $plazo = $inversion->plazo;
        $tipoTaza = $inversion->tipoTaza->valor;
        $tipoPlazo = $inversion->tipoPlazo->valor;

        $cuota = $this->calcularCuota($monto, $interes, $plazo, $tipoTaza, $tipoPlazo);
        $this->generarCuotasInversion($inversion, $cuota);
    }

    private function generarCuotasInversion(Inversion $inversion, $cuota)
    {
        $plazo = $inversion->plazo;
        $fecha = $inversion->fecha_inicio;

        for ($i = 0; $i < $plazo; $i++) {
            $fecha = date('Y-m-d', strtotime($fecha . ' + 1 month'));

            $pago = new Pago_Inversion();
            $pago->monto = round($cuota, 2);
            $pago->fecha = $fecha;
            $pago->realizado = false;
            $pago->inversion_id = $inversion->id;
            $pago->save();
        }
    }

    private function calcularCuota($monto, $interes, $plazo, $tipoTaza, $tipoPlazo)
    {
        $taza = $interes / 100;
        $taza = $taza / 12;

        $cuota = 0;

        if ($tipoPlazo == 'Anios') {
            $plazo = $plazo * 12;
        }

        if ($tipoTaza == 'Fija') {
            $cuota = $monto * ($taza / (1 - pow((1 + $taza), -$plazo)));
        } else {
            $cuota = $monto * ($taza / (1 - pow((1 + $taza), -$plazo)));
        }

        $cuota = round($cuota, 2);

        return $cuota;
    }
}
