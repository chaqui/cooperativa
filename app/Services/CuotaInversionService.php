<?php

namespace App\Services;

use App\Models\Inversion;
use App\Models\Pago_Inversion;

class CuotaInversionService extends CuotaService
{

    public function calcularCuotaInversion(Inversion $inversion)
    {
        $monto = $inversion->monto;
        $interes = $inversion->interes;
        $plazo = $inversion->plazo;
        $tipoTaza = $inversion->tipoTaza->valor;
        $tipoPlazo = $inversion->tipoPlazo->valor;

        $cuota = $this->calcularCuota($monto, $interes, $plazo, $tipoTaza, $tipoPlazo);
        $this->generarCuotasInversion($inversion, $cuota);
    }

    private function generarCuotasInversion(Inversion $inversion, $cuota)
    {
        $plazo = $this->calcularPlazo($inversion->plazo, $inversion->tipoPlazo->nombre);
        $fecha = $inversion->fecha_inicio;

        for ($i = 0; $i < $plazo; $i++) {
            $fecha = date('Y-m-d', strtotime($fecha . ' + 1 month'));

            $pago = new Pago_Inversion();
            $pago->monto = round($cuota, 2);
            $pago->fecha = $fecha;
            $pago->realizado = false;
            $pago->inversion_id = $inversion->id;
            $pago->save();
        }
    }

    public function getCuotasInversion(Inversion $inversion)
    {
        return Pago_Inversion::where('inversion_id', $inversion->id)->get();
    }

    public function realizarPago(Pago_Inversion $pago)
    {
        $pago->realizado = true;
        $pago->save();
    }

    public function getPagoInversion(string $id): Pago_Inversion
    {
        return Pago_Inversion::findOrFail($id);
    }


}
