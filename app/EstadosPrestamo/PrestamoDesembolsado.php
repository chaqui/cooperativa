<?php

namespace App\EstadosPrestamo;


use App\Constants\EstadoPrestamo;

use App\Models\Prestamo_Hipotecario;
use App\Services\CuotaHipotecaService;

class PrestamoDesembolsado extends EstadoBasePrestamo
{

    private $cuotaHipotecariaService;
    public function __construct(CuotaHipotecaService $cuotaHipotecariaService)
    {
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
        parent::__construct(EstadoPrestamo::$APROBADO, EstadoPrestamo::$DESEMBOLZADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $razon = null)
    {
        $prestamo->fecha_desembolso = now();
        parent::cambiarEstado($prestamo);
        $this->cuotaHipotecariaService->calcularCuotas($prestamo);

    }
}
