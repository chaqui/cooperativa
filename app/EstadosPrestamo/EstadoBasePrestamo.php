<?php

namespace App\EstadosPrestamo;

use App\Models\Prestamo_Hipotecario;

class EstadoBasePrestamo
{
    private $estadoFin;
    private $estadoInicio;

    public function __construct($estadoInicio, $estadoFin)
    {
        $this->estadoInicio = $estadoInicio;
        $this->estadoFin = $estadoFin;
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $razon = null)
    {
        if(!$this->estadoInicio){
            $prestamo->estado = $this->estadoFin;
            $prestamo->save();
        }
        else if ($prestamo->estado == $this->estadoInicio) {
            $prestamo->estado = $this->estadoFin;
            $prestamo->save();
        }
    }
}
