<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;

use App\Services\CuotaHipotecaService;
use App\Services\RetiroService;

class ControladorEstado
{

    private $cuotaHipotecariaService;


    private RetiroService $retiroService;

    public function __construct(CuotaHipotecaService $cuotaHipotecariaService, RetiroService $retiroService)
    {
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
        $this->retiroService = $retiroService;
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
                return new PrestamoAprobado($this->retiroService);
            case EstadoPrestamo::$DESEMBOLZADO:
                return new PrestamoDesembolsado($this->cuotaHipotecariaService);
            case EstadoPrestamo::$FINALIZADO:
                return new PrestamoFinalizado();
            default:
                return new EstadoBasePrestamo(null, null);
        }
    }
}
