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
        $tipoPlazo = $inversion->tipoPlazo->valor;
        $fecha_inicio = $inversion->fecha_inicio;

        $plazo = $this->calcularPlazo($plazo, $tipoPlazo);
        $cuotaDiaria = $this->gananciaDiaria($interes, $monto);

        for ($i = 1; $i <= $plazo; $i++) {
            $cuota = $this->calcularCuota($cuotaDiaria, $i, $fecha_inicio);
            $fecha_inicio = $fecha_inicio->addMonth();
            Pago_Inversion::create([
                'inversion_id' => $inversion->id,
                'monto' => $cuota,
                'fecha_pago' => $fecha_inicio,
                'realizado' => false
            ]);
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
}
