<?php

namespace App\EstadosInversion;

use App\Constants\EstadoInversion;
use App\Estado\Estado;
use App\Models\HistorialEstado;
use App\Models\Inversion;

class EstadoBaseInversion extends Estado
{
    public function __construct($estadoInicio, $estadoFin)
    {
        parent::__construct($estadoInicio, $estadoFin);
    }

    public function cambiarEstado(Inversion $inversion, $data)
    {
        $estadoOriginal = $inversion->id_estado;

        // Validar estado inicial
        if ($this->estadoInicio !== null && $estadoOriginal != $this->estadoInicio) {
            throw new \Exception(
                "Estado inválido para cambio: la inversión #{$inversion->id} está en estado " .
                    "{$estadoOriginal}, pero debe estar en estado {$this->estadoInicio}"
            );
        }

        // Actualizar estado de la inversión
        $inversion->id_estado = $this->estadoFin;
        $inversion->updated_at = now(); // Actualizar timestamp
        $inversion->save();

        $historico =  HistorialEstado::generarHistoricoInversion(
            $inversion->id,
            $this->estadoFin,
            $data
        );
        $historico->save();
    }
}
