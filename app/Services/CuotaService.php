<?php

namespace App\Services;

abstract class CuotaService
{
    protected function calcularCuota($monto, $interes, $plazo, $tipoTaza, $tipoPlazo)
    {
        $taza = $interes / 100;
        $taza = $taza / 12;

        $cuota = 0;

        $plazo = $this->calcularPlazo($plazo, $tipoPlazo);
        if ($tipoTaza == 'Fija') {
            $cuota = $monto * ($taza / (1 - pow((1 + $taza), -$plazo)));
        } else {
            $cuota = $monto * ($taza / (1 - pow((1 + $taza), -$plazo)));
        }

        $cuota = round($cuota, 2);

        return $cuota;
    }

    protected  function calcularPlazo($plazo, $tipoPlazo)
    {
        if ($tipoPlazo == 'Anios') {
            $plazo = $plazo * 12;
        }
        return $plazo;
    }
}
