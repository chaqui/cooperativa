<?php

namespace App\EstadosPrestamo;


use App\Constants\EstadoPrestamo;

use App\Models\Prestamo_Hipotecario;
use App\Services\ArchivoService;
use App\Services\CuotaHipotecaService;
use App\Services\PrestamoPdfService;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;

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
        $prestamo->fecha_inicio = $data['fecha'] ?? now();
        parent::cambiarEstado($prestamo, $data);

        $cuotaPagada = 0;
        $this->log("Las cuotas pagadas son: {$cuotaPagada}");
        $this->cuotaHipotecariaService->calcularCuotas($prestamo, $cuotaPagada);

        $this->generarYGuardarEstadoDeCuenta($prestamo);
    }

    private function generarYGuardarEstadoDeCuenta($prestamo)
    {
        $this->log("Generando estado de cuenta para el préstamo #{$prestamo->id}");
        DB::commit();
        $prestamoPdfService = app()->make(PrestamoPdfService::class);
        $pdf = $prestamoPdfService->generarEstadoCuentaPdf($prestamo->id, true);

        $path = storage_path('app/estados_cuenta/');

        $fileName = 'estado_cuenta_prestamo_' . $prestamo->id . '.pdf';
        $pathArchivo = $this->archivoService->guardarArchivo($pdf, $path, $fileName);
        $prestamo->estado_cuenta_path = $pathArchivo;
        $prestamo->save();
    }
}
