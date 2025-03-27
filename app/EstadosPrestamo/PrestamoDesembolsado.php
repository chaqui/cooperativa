<?php

namespace App\EstadosPrestamo;


use App\Constants\EstadoPrestamo;

use App\Models\Prestamo_Hipotecario;
use App\Services\CuotaHipotecaService;
use App\Services\CuentaInternaService;

class PrestamoDesembolsado extends EstadoBasePrestamo
{

    private $cuotaHipotecariaService;


    public function __construct(CuotaHipotecaService $cuotaHipotecariaService)
    {
        $this->cuotaHipotecariaService = $cuotaHipotecariaService;
        parent::__construct(EstadoPrestamo::$APROBADO, EstadoPrestamo::$DESEMBOLZADO);
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {

        if (!$data['numero_documento']) {
            throw new \Exception('El número de documento es requerido');
        }
        if (!$data['tipo_documento']) {
            throw new \Exception('El tipo de documento es requerido');
        }
        $prestamo->fecha_inicio = now();
        parent::cambiarEstado($prestamo, $data);
        $dataCuentaInterna = [
            'ingreso' => 0,
            'egreso' => $prestamo->monto,
            'descripcion' => 'Desembolso de prestamo hipotecario con id ' . $prestamo->id .
                ' con monto de ' . $prestamo->monto . ' con numero de documento ' . $data['numero_documento'] .
                ' y tipo de documento ' . $data['tipo_documento']
        ];
        $this->cuotaHipotecariaService->calcularCuotas($prestamo);
    }
}
