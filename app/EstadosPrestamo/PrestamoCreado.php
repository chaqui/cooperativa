<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;

use App\Models\Prestamo_Hipotecario;

class PrestamoCreado extends EstadoBasePrestamo
{
    public function __construct()
    {
        parent::__construct(null, EstadoPrestamo::$CREADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        parent::cambiarEstado($prestamo, $data);
    }
}
