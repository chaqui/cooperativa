<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;
use App\Services\DepositoService;
use App\Traits\ErrorHandler;

class PrestamoCancelado extends EstadoBasePrestamo
{
    use ErrorHandler;

    private string $cancelacionPorReestructuracion = '23';
    private string $cancelacionPorPagoTotal = '24';
    private string $cancelacionPorAmpliacion = '25';
    private DepositoService $depositoService;

    public function __construct(DepositoService $depositoService)
    {
        parent::__construct(null, EstadoPrestamo::$CANCELADO);
        $this->depositoService = $depositoService;
    }

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        if (!$data['razon']) {
            $this->lanzarExcepcionConCodigo('La razón es requerida');
        }
        if ($prestamo->interesPendiente() > 0) {
            $this->lanzarExcepcionConCodigo('No se puede cancelar el préstamo mientras haya intereses pendientes.');
        }
        $prestamo->motivo_cancelacion = $data['razon'];
        $prestamo->fecha_cancelacion = now();
        if (isset($data['tipo']) && $data['tipo'] == $this->cancelacionPorReestructuracion) {
            $this->retornarFondosExistentes($prestamo, $data['razon']);
        } elseif (isset($data['tipo']) && $data['tipo'] == $this->cancelacionPorAmpliacion) {
            $this->retornarFondosExistentes($prestamo, $data['razon']);
        } elseif (isset($data['tipo']) && $data['tipo'] == $this->cancelacionPorPagoTotal) {
            $pagosPendientes = $prestamo->pagos()->where('realizado', false)->count();
            if ($pagosPendientes > 0) {
                throw new \Exception('No se puede cancelar el préstamo por pago total mientras haya pagos pendientes.');
            }
        } elseif (!isset($data['tipo'])) {
            throw new \Exception('El tipo de cancelación es requerido');
        } else {
            throw new \Exception('El tipo de cancelación es inválido');
        }
        parent::cambiarEstado($prestamo, $data);
    }

    private function retornarFondosExistentes(Prestamo_Hipotecario $prestamo, $descripcion)
    {

        if ($prestamo->existente) {
            return;
        }
        $fondos = $prestamo->saldoPendienteCapital();
        $idCuenta = $prestamo->retiro->tipo_cuenta_interna_id;
        if ($fondos > 0) {
            $data = [
                'tipo_documento' => 'RETIRO_CANCELACION_PRESTAMO',
                'monto' => $fondos,
                'numero_documento' => 'RC-' . $prestamo->codigo,
                'motivo' => $descripcion,
                'saldo' => $fondos,
                'tipo_cuenta_interna_id' => $idCuenta,
                'id_cuenta' => $idCuenta,
                'existente' => true,
                'fecha' => now(),
            ];
            $this->depositoService->crearDepositoInterno($data);
        }
    }
}
