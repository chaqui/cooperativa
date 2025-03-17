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
        if (!$this->estadoInicio) {
            $inversion->estado_id = $this->estadoFin;
            $inversion->save();
        } else if ($inversion->estado_id == $this->estadoInicio) {
            $inversion->estado_id = $this->estadoFin;
            $inversion->save();
        }
        else{
            throw new \Exception("El estado actual de la inversion no es el correcto");
        }
        $historico=  HistorialEstado::generarHistorico($inversion->id, $this->estadoFin, $data);
        $historico->save();
    }
}
