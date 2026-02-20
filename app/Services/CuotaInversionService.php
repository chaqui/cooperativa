<?php

namespace App\Services;

use App\Models\Inversion;
use App\Models\Pago_Inversion;
use App\Traits\Loggable;
use DateTime;

class CuotaInversionService extends CuotaService
{

    use Loggable;
    public function createCuotas(Inversion $inversion)
    {
        $cuotas = $this->calcularPlazo($inversion->plazo, $inversion->tipo_plazo);
        $gananciaDiaria = $this->gananciaDiaria($inversion->interes, $inversion->monto);
        for ($i = 1; $i <= $cuotas; $i++) {
            $pago = new Pago_Inversion();
            $pago->inversion_id = $inversion->id;
            $pago->monto_interes = $this->calcularCuotaInversion($gananciaDiaria, $i, $inversion->fecha_inicio);
            $pago->monto_isr = $pago->monto_interes * 0.1;
            $pago->monto = $pago->monto_interes - $pago->monto_isr;
            $pago->fecha = $this->sumarMesesDesdeFecha($inversion->fecha_inicio, $i);
            $pago->realizado = false;
            $pago->fecha_pago = $this->obtenerSiguienteDiaHabil($pago->fecha);
            $pago->existente = $this->esPagoExistente($pago, $inversion->exists);
            $pago->save();
        }
    }

    private function esPagoExistente( $pago,  $inversionExistente): bool
    {
        $this->log("Verificando existencia de pago para inversión existente: " . ($inversionExistente ? 'Sí' : 'No') );
       if($inversionExistente){
            $fechaHoy = new DateTime();
            if ($pago->fecha_pago instanceof \DateTimeInterface) {
                $fechaPago = $pago->fecha_pago;
            } elseif (is_numeric($pago->fecha_pago)) {
                $fechaPago = (new DateTime())->setTimestamp((int) $pago->fecha_pago);
            } elseif (is_string($pago->fecha_pago) && $pago->fecha_pago !== '') {
                $fechaPago = new DateTime($pago->fecha_pago);
            } else {
                return false;
            }
            $this->log("Comparando fecha de pago: {$fechaPago->format('Y-m-d')} con fecha actual: {$fechaHoy->format('Y-m-d')}");
            $resultado = $fechaPago <= $fechaHoy;
            $this->log("Resultado de la comparación: " . ($resultado ? 'Pago existente' : 'Pago no existente'));
            return $resultado;
       }
       return false;
    }

    public function getCuotasInversion(Inversion $inversion)
    {
        return Pago_Inversion::where('inversion_id', $inversion->id)->get();
    }

    public function realizarPago($id, $no_boleta)
    {
        $pago = Pago_Inversion::findOrFail($id);
        $pago->realizado = true;
        $pago->no_boleta = $no_boleta;
        $pago->save();
    }

    public function getPagoInversion(string $id): Pago_Inversion
    {
        return Pago_Inversion::findOrFail($id);
    }

    public function deletePagoInversion(string $idInversion): void
    {
        Pago_Inversion::where('inversion_id', $idInversion)->delete();
    }
    private function sumarMesesDesdeFecha($fecha, int $meses)
    {
        // Handle both DatePoint objects and DateTime objects
        if ($fecha instanceof \Symfony\Component\Clock\DatePoint) {
            $nuevaFecha = \DateTime::createFromInterface($fecha);
        } elseif ($fecha instanceof \DateTime) {
            $nuevaFecha = clone $fecha;
        } else {
            $nuevaFecha = new \DateTime($fecha);
        }
        return $nuevaFecha->modify("+{$meses} months");
    }
    private function obtenerSiguienteDiaHabil($fecha)
    {
        // Si la fecha es nula, usar la fecha actual
        if ($fecha === null) {
            $fecha = new DateTime();
        }

        // Si no es un objeto DateTime, convertirlo
        if (!$fecha instanceof DateTime) {
            $fecha = new DateTime($fecha);
        }

        $fechaCopia = clone $fecha;
        $diaSemana = (int)$fechaCopia->format('N'); // 1 (lunes) a 7 (domingo)

        // Si es sábado (6) o domingo (7), avanzar al lunes
        if ($diaSemana >= 6) {
            $diasAgregar = 8 - $diaSemana; // 2 para sábado, 1 para domingo
            $fechaCopia->modify("+{$diasAgregar} days");
        }

        return $fechaCopia;
    }
    public function obtenerCuotasFecha($fecha)
    {
        return Pago_Inversion::where('fecha_pago', $fecha)->get();
    }

    public function obtenerCuotasHoy()
    {
        $pagos = Pago_Inversion::all();
        $cuotasHoy    = collect();
        foreach ($pagos as $pago) {
            $cuotas = $pago->retiros()->where('realizado', false)->get();
            if ($cuotas->isEmpty()) {
                continue;
            }
            $inversion = $pago->inversion;
            foreach ($cuotas as $cuota) {
                $cuota->codigoInversion = $inversion->codigo;
                $cuota->nombreCliente = $inversion->cliente->getFullNameAttribute();
                $cuota->cuenta_recaudadora = $inversion->cuenta_recaudadora . '-' . $inversion->cuentaRecaudadora->nombre_banco;
                $cuota->monto_isr = $pago->monto_isr;
            }
            $cuotasHoy = $cuotasHoy->merge($cuotas);
        }

        return $cuotasHoy;
    }
}
