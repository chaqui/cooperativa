<?php

namespace App\Services;

use App\Models\Inversion;
use App\Models\Pago_Inversion;

class CuotaInversionService extends CuotaService
{



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
