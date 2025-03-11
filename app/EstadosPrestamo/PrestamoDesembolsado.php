<?php

namespace App\EstadosPrestamo;


use App\Constants\EstadoPrestamo;

use App\Models\Prestamo_Hipotecario;
use App\Services\CuotaHipotecaService;
use App\Services\CuentaInternaService;

class PrestamoDesembolsado extends EstadoBasePrestamo
{

    private $cuotaHipotecariaService;

    private $cuentaInternaService;
    public function __construct(CuotaHipotecaService $cuotaHipotecariaService, CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
        parent::__construct(EstadoPrestamo::$APROBADO, EstadoPrestamo::$DESEMBOLZADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {


        if (!$data['no_documento_desembolso']) {
            throw new \Exception('El número de documento es requerido');
        }
        if (!$data['tipo_documento_desembolso']) {
            throw new \Exception('El tipo de documento es requerido');
        }
        $prestamo->fecha_inicio = now();
        parent::cambiarEstado($prestamo, $data);
        $dataCuentaInterna = [
            'ingreso' => 0,
            'egreso' => $prestamo->monto,
            'descripcion' => 'Desembolso de prestamo hipotecario con id ' . $prestamo->id .
                ' con monto de ' . $prestamo->monto . ' con numero de documento ' . $data['no_documento_desembolso'] .
                ' y tipo de documento ' . $data['tipo_documento_desembolso']
        ];
        $this->cuentaInternaService->createCuenta($dataCuentaInterna);
        $this->cuotaHipotecariaService->calcularCuotas($prestamo);
    }
}
