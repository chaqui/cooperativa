<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;

use App\Services\CuotaHipotecaService;
use App\Services\CuentaInternaService;

class ControladorEstado
{

    private $cuotaHipotecariaService;

    private $cuentaInternaService;

    public function __construct(CuotaHipotecaService $cuotaHipotecariaService, CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
    }

    public  function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        $estado = self::getEstado($data['estado']);
        $estado->cambiarEstado($prestamo, $data);
    }

    private  function getEstado($estado)
    {
        switch ($estado) {
            case EstadoPrestamo::$CREADO:
                return new PrestamoCreado();
            case EstadoPrestamo::$APROBADO:
                return new PrestamoAprobado();
            case EstadoPrestamo::$DESEMBOLZADO:
                return new PrestamoDesembolsado($this->cuotaHipotecariaService, $this->cuentaInternaService);
            case EstadoPrestamo::$FINALIZADO:
                return new PrestamoFinalizado();
            default:
                return new EstadoBasePrestamo(null, null);
        }
    }
}
