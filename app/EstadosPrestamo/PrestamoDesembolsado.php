<?php

namespace App\EstadosPrestamo;


use App\Constants\EstadoPrestamo;

use App\Models\Prestamo_Hipotecario;
use App\Services\ArchivoService;
use App\Services\CuotaHipotecaService;
use App\Services\PrestamoService;
use App\Traits\Loggable;

class PrestamoDesembolsado extends EstadoBasePrestamo
{

    private $cuotaHipotecariaService;

    private ArchivoService $archivoService;

    use Loggable;


    public function __construct(CuotaHipotecaService $cuotaHipotecariaService, ArchivoService $archivoService)
    {
        $this->archivoService = $archivoService;
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
        $this->cuotaHipotecariaService->calcularCuotas($prestamo);
        $this->generarYGuardarEstadoDeCuenta($prestamo);
    }

    private function generarYGuardarEstadoDeCuenta($prestamo)
    {
        $this->log("Generando estado de cuenta para el préstamo #{$prestamo->id}");
        $prestamoService = app()->make(PrestamoService::class);
        $pdf = $prestamoService->generarEstadoCuentaPdf($prestamo->id);

        $path = storage_path('app/estados_cuenta/');

        $fileName = 'estado_cuenta_prestamo_' . $prestamo->id . '.pdf';
        $pathArchivo = $this->archivoService->guardarArchivo($pdf, $path, $fileName);
        $prestamo->estado_cuenta_path = $pathArchivo;
        $prestamo->save();
    }
}
