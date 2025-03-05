<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;
use PhpParser\Node\Expr\Cast\String_;

class PrestamoCancelado extends EstadoBasePrestamo
{
    public function __construct()
    {
        parent::__construct(EstadoPrestamo::$CREADO, EstadoPrestamo::$CANCELADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $razon = null)
    {
        $prestamo->fecha_cancelacion = now();
        $prestamo->razon_cancelacion = $razon;
        parent::cambiarEstado($prestamo, $razon);
    }
}
