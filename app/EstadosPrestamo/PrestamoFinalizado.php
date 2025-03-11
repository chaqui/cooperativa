<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;

class PrestamoFinalizado extends EstadoBasePrestamo
{
    public function __construct()
    {
        parent::__construct(EstadoPrestamo::$DESEMBOLZADO, EstadoPrestamo::$FINALIZADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        parent::cambiarEstado($prestamo, $data);
    }
}
