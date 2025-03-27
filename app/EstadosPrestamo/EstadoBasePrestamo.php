<?php

namespace App\EstadosPrestamo;

use App\Estado\Estado;
use App\Models\Prestamo_Hipotecario;
use App\Models\HistorialEstado;

class EstadoBasePrestamo extends Estado
{
    public function __construct($estadoInicio, $estadoFin)
    {
        parent::__construct($estadoInicio, $estadoFin);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        $estadoOriginal = $prestamo->estado_id;

        // Validar estado inicial si está definido
        if ($this->estadoInicio !== null && $estadoOriginal != $this->estadoInicio) {
            throw new \Exception(
                "Estado inválido para cambio: el préstamo #{$prestamo->id} está en estado " .
                    "{$estadoOriginal}, pero debe estar en estado {$this->estadoInicio}"
            );
        }

        // Actualizar estado del préstamo
        $prestamo->estado_id = $this->estadoFin;
        $prestamo->updated_at = now(); // Actualizar timestamp

        $prestamo->save();
        $historico =  HistorialEstado::generarHistoricoPrestamo(
            $prestamo->id,
            $this->estadoFin,
            $data
        );
        $historico->save();
    }
}
