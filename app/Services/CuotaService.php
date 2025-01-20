<?php

namespace App\Services;

abstract class CuotaService
{
    protected function gananciaDiaria($interes, $monto)
    {
        $taza = $interes / 100 / 365;
        return $monto * $taza;
    }


    protected function calcularCuota($gananciaDiaria, $numeroMes, $fechaInicio){
        $dias = $this->obtenerDiasDelMes($fechaInicio, $numeroMes);

        return $gananciaDiaria * $dias;
    }

    private function obtenerDiasDelMes($fechaInicio, $numeroMes)
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
}
