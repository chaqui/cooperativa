<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;

class PrestamoAprobado extends EstadoBasePrestamo
{
    public function __construct()
    {
        parent::__construct(EstadoPrestamo::$CREADO, EstadoPrestamo::$APROBADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        parent::cambiarEstado($prestamo, $data);
    }
}
