<?php

namespace App\Services;

abstract class CuotaService
{
    protected function gananciaDiaria($interes, $monto)
    {
        $taza = $this->calcularTaza($interes)/ 365;
        return $monto * $taza;
    }


    protected function calcularCuotaInversion($gananciaDiaria, $numeroMes, $fechaInicio){
        $dias = $this->obtenerDiasDelMes($fechaInicio, $numeroMes);

        return $gananciaDiaria * $dias;
    }

    protected function obtenerDiasDelMes($fechaInicio, $numeroMes)
    {
        $fecha = new \DateTime($fechaInicio);
        $fecha->modify("+$numeroMes month");
        return cal_days_in_month(CAL_GREGORIAN, $fecha->format('m'), $fecha->format('Y'));
    }

    protected  function calcularPlazo($plazo, $tipoPlazo)
    {
        if ($tipoPlazo == 'Anios') {
            $plazo = $plazo * 12;
        }
        return $plazo;
    }

    protected function calcularTaza($interes)
    {
        return $interes / 100;
    }
}
