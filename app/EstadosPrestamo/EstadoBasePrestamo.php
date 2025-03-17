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
        if (!$this->estadoInicio) {
            $prestamo->estado_id = $this->estadoFin;
            $prestamo->save();
        } else if ($prestamo->estado_id == $this->estadoInicio) {
            $prestamo->estado_id = $this->estadoFin;
            $prestamo->save();
        }
        else{
            throw new \Exception("El estado actual del prestamo no es el correcto");
        }
        $historico=  HistorialEstado::generarHistoricoPrestamo($prestamo->id, $this->estadoFin, $data);
        $historico->save();
    }
}
