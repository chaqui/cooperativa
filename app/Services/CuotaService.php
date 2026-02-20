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
        $taza = $this->calcularTaza($interes) / 365;
        return $monto * $taza;
    }


    protected function calcularCuotaInversion($gananciaDiaria, $numeroMes, $fechaInicio)
    {
        $dias = $this->obtenerDiasDelMes($fechaInicio, $numeroMes);

        return $gananciaDiaria * $dias;
    }

    protected function obtenerDiasDelMes($fechaInicio, $numeroMes)
    {
        // Handle both DatePoint objects and string dates
        if ($fechaInicio instanceof \Symfony\Component\Clock\DatePoint) {
            // DatePoint implements DateTimeInterface, so we can use it directly
            $fecha = clone $fechaInicio; // Clone to avoid modifying the original
        } else {
            $fecha = new \DateTime($fechaInicio);
        }
        $fecha->modify("+$numeroMes month");
        return cal_days_in_month(CAL_GREGORIAN, $fecha->format('m'), $fecha->format('Y'));
    }


    protected function calcularTaza($interes)
    {
        return $interes / 100;
    }
}
