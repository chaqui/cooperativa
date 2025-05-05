<?php

namespace App\Services;

use App\Constants\TipoPlazo;
use App\Traits\Calculos;
use App\Traits\Loggable;

abstract class CuotaService
{
    use Loggable;

    use Calculos;
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


    protected function calcularTaza($interes)
    {
        return $interes / 100;
    }
}
