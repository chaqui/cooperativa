<?php

namespace App\EstadosInversion;

use App\Constants\EstadoInversion;
use App\Estado\Estado;
use App\Models\HistorialEstado;
use App\Models\Inversion;
use App\Traits\Loggable;

class EstadoBaseInversion extends Estado
{
    use Loggable;
    public function __construct($estadoInicio, $estadoFin)
    {
        parent::__construct($estadoInicio, $estadoFin);
    }

    public function cambiarEstado(Inversion $inversion, $data)
    {
        $this->log("Iniciando cambio de estado para inversión #{$inversion->id}, código {$inversion->codigo}");
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
