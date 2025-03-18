<?php

namespace App\Services;

use App\Models\Inversion;
use App\Models\Pago_Inversion;

class CuotaInversionService extends CuotaService
{

    public function createCuotas(Inversion $inversion)
    {
        $cuotas = $this->calcularPlazo($inversion->plazo, $inversion->tipo_plazo);
        $gananciaDiaria = $this->gananciaDiaria($inversion->interes, $inversion->monto);
        for ($i = 1; $i <= $cuotas; $i++) {
            $pago = new Pago_Inversion();
            $pago->inversion_id = $inversion->id;
            $pago->montoInteres = $this->calcularCuotaInversion($gananciaDiaria, $i, $inversion->fecha_inicio);
            $pago->montoISR = $pago->montoInteres * 0.1;
            $pago->monto = $pago->montoInteres - $pago->montoISR;
            $pago->fecha = $this->sumarMesesDesdeFecha($inversion->fecha_inicio, $i);
            $pago->realizado = false;
            $pago->fecha_pago = $this->obtenerSiguienteDiaHabil($pago->fecha);
            $pago->save();
        }
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
    private function sumarMesesDesdeFecha(\DateTime $fecha, int $meses): \DateTime
    {
        $nuevaFecha = clone $fecha;
        return $nuevaFecha->modify("+{$meses} months");
    }
    private function obtenerSiguienteDiaHabil(\DateTime $fecha): \DateTime
    {
        $diaSemana = (int) $fecha->format('N'); // 1 (lunes) - 7 (domingo)
        while ($diaSemana >= 6) { // 6 (sábado) y 7 (domingo) no son hábiles
            $fecha = $fecha->modify('+1 day');
            $diaSemana = (int) $fecha->format('N');
        }
        return $fecha;
    }

    public function obtenerCuotasFecha($fecha)
    {
        return Pago_Inversion::where('fecha_pago', $fecha)->get();
    }

    public function obtenerCuotasHoy()
    {
        $hoy = (new \DateTime())->format('Y-m-d');
        return Pago_Inversion::where('fecha_pago', $hoy)->get();
    }


}
