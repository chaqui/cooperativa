<?php

namespace App\EstadosPrestamo;

use App\Models\Prestamo_Hipotecario;
use App\Models\HistorialEstado;

class EstadoBasePrestamo
{
    private $estadoFin;
    private $estadoInicio;

    public function __construct($estadoInicio, $estadoFin)
    {
        $this->estadoInicio = $estadoInicio;
        $this->estadoFin = $estadoFin;
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
        $historico=  HistorialEstado::generarHistorico($prestamo->id, $this->estadoFin, $data);
        $historico->save();
    }
}
