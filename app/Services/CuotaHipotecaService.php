<?php

namespace App\Services;

use App\Models\Prestamo_Hipotecario;
use App\Models\Pago;

class CuotaHipotecaService extends CuotaService{
    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario)
    {
        $monto = $prestamoHipotecario->monto;
        $interes = $prestamoHipotecario->interes;
        $plazo = $prestamoHipotecario->plazo;
        $tipoTaza = $prestamoHipotecario->tipoTaza->valor;
        $tipoPlazo = $prestamoHipotecario->tipoPlazo->nombre;

        $cuota = $this->calcularCuota($monto, $interes, $plazo, $tipoTaza, $tipoPlazo);

        $this->generarCuotas($prestamoHipotecario, $cuota);
    }

    private function generarCuotas(Prestamo_Hipotecario $prestamoHipotecario, $cuota)
    {

        $plazo = $prestamoHipotecario->plazo;
        $fecha = $prestamoHipotecario->fecha_inicio;

        for ($i = 0; $i < $plazo; $i++) {
            $fecha = date('Y-m-d', strtotime($fecha . ' + 1 month'));

            $pago = new Pago();
            $pago->monto = round($cuota, 2);
            $pago->fecha_pago = $fecha;
            $pago->realizado = false;
            $pago->id_prestamo = $prestamoHipotecario->id;
            $pago->save();
        }
    }

    public function getPago(string $id): Pago
    {
        return Pago::findOrFail($id);
    }

    public function realizarPago(Pago $pago)
    {
        $pago->realizado = true;
        $pago->save();
    }

    public function getPagos(Prestamo_Hipotecario $prestamoHipotecario)
    {
        return Pago::where('id_prestamo', $prestamoHipotecario->id)->get();
    }

}
