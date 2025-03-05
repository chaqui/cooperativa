<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;

use App\Services\CuotaHipotecaService;

class ControladorEstado
{

    private $cuotaHipotecariaService;

    public function __construct(CuotaHipotecaService $cuotaHipotecariaService)
    {
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
    }

    public  function cambiarEstado(Prestamo_Hipotecario $prestamo, $estado, $razon = null)
    {
        $estado = self::getEstado($estado);
        $estado->cambiarEstado($prestamo, $razon);
    }

    private  function getEstado($estado)
    {
        switch ($estado) {
            case EstadoPrestamo::$CREADO:
                return new PrestamoCreado();
            case EstadoPrestamo::$APROBADO:
                return new PrestamoAprobado();
            case EstadoPrestamo::$DESEMBOLZADO:
                return new PrestamoDesembolsado($this->cuotaHipotecariaService);
            case EstadoPrestamo::$FINALIZADO:
                return new PrestamoFinalizado();
            default:
                return new EstadoBasePrestamo(null, null);
        }
    }
}
