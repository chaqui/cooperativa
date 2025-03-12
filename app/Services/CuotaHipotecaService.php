<?php

namespace App\Services;

use App\Models\Prestamo_Hipotecario;
use App\Models\Pago;

class CuotaHipotecaService extends CuotaService
{


    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario)
    {

        $cuota = $this->calcularCuota($prestamoHipotecario->monto, $prestamoHipotecario->interes,  $prestamoHipotecario->plazo, $prestamoHipotecario->tipoPlazo->valor);

        $this->generarCuotas($prestamoHipotecario, $cuota);
    }

    private function generarCuotas(Prestamo_Hipotecario $prestamoHipotecario, $cuota)
    {

        $plazo = $prestamoHipotecario->plazo;
        $fecha = $prestamoHipotecario->fecha_inicio;
        $pagoAnterior = null;

        if (!$this->esFechaValida($fecha)) {
            $pagoAnterior = $this->generarPagoInvalido($prestamoHipotecario);
        }

        for ($i = 0; $i < $plazo; $i++) {
            $pagoAnterior = $this->generarPago($cuota, $pagoAnterior, $prestamoHipotecario);
        }
    }

    private function generarPago($cuota,  $pagoAnterior, $prestamo)
    {
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->interes = $this->calcularInteres($cuota,  $this->calcularTaza($prestamo->interes));
        $pago->capital = $cuota - $pago->interes;
        $pago->fecha = $this->obtenerFechaSiguienteMes($pagoAnterior->fecha);
        $pago->saldo = $pagoAnterior->saldo - $pago->capital;
        $pago->realizado = false;
        $pago->id_pago_anterior = $pagoAnterior->id;
        $pago->save();
        return $pago;
    }


    private function generarPagoInvalido($prestamo)
    {
        $fecha = $prestamo->fecha_inicio;
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->capital = 0;
        $pago->interes = $this->calcularInteresDiario($prestamo->interes,  $fecha);
        $pago->fecha = $this->obtenerFechaSiguienteMes($fecha);
        $pago->saldo = $prestamo->monto;
        $pago->realizado = false;
        $pago->save();
        return $pago;
    }

    public function getPago(string $id): Pago
    {
        return Pago::findOrFail($id);
    }

    public function realizarPago($data)
    {
        $pago = $this->getPago($data['id']);
        $prestamo = $pago->prestamo();
        if($pago->capital > $data['capital']){
           throw new \Exception('El capital pagado no puede ser menor al capital de la cuota');
        }
        $pago->fecha_pago = date('Y-m-d');
        $pago->realizado = true;
        $pago->monto_pagado = $data['monto'];
        $pago->capital_pagado = $data['capital'];
        $pago->saldo = $pago->saldo - $pago->capital_pagado;
        $pago->save();
        if($data['capital'] > $pago->capital && $pago->saldo > 0 && $pago->pagoSiguiente()){
            $this->actualizarSiguentesPago($pago, $prestamo->first());
        }
    }

    private function actualizarSiguentesPago(Pago $pago, Prestamo_Hipotecario $prestamoHipotecario)
    {
        $pagoSiguiente = $pago->pagoSiguiente();
        $pagoSiguiente->interes = $this->calcularInteres($pago->saldo,  $this->calcularTaza($prestamoHipotecario->interes));
        $pagoSiguiente->capital = $pago->saldo - $pagoSiguiente->interes;
        $pagoSiguiente->saldo = $pago->saldo - $pagoSiguiente->capital;
        $pagoSiguiente->save();
        if($pagoSiguiente->saldo > 0){

        if($pagoSiguiente->pagoSiguiente()){
            $this->actualizarSiguentesPago($pagoSiguiente,$prestamoHipotecario);
        }
        else{
            $this->generarPago($pagoSiguiente->saldo, $pagoSiguiente, $prestamoHipotecario);
        }
    }
    }

    public function getPagos(Prestamo_Hipotecario $prestamoHipotecario)
    {
        return Pago::where('id_prestamo', $prestamoHipotecario->id)->get();
    }

    private function esFechaValida($fecha)
    {
        $dia = date('j', strtotime($fecha));
        return $dia >= 1 && $dia <= 5;
    }

    private function calcularCuota($monto, $interes, $plazo, $tipoPlazo)
    {
        $tasaInteresMensual = $this->calcularTaza($interes);
        $numeroPagos = $this->calcularPlazo($plazo, $tipoPlazo);

        return ($monto * $tasaInteresMensual) / (1 - pow(1 + $tasaInteresMensual, -$numeroPagos));
    }

    private function calcularInteres($monto, $taza)
    {
        return $monto * $taza;
    }

    private function calcularInteresDiario($interes, $fecha)
    {

        return ($interes / $this->obtenerDiasDelMes($fecha, 0)) * $this->calcularDiasFaltantes($fecha);
    }

    private function calcularDiasFaltantes($fecha)
    {
        $fechaActual = $fecha;
        $fechaSiguiente = $this->obtenerFechaSiguienteMes($fecha);
        $diferencia = $fechaActual->diff($fechaSiguiente);
        return $diferencia->days;
    }
    private function obtenerFechaSiguienteMes($fecha)
    {
        $fecha = date('Y-m-d', strtotime($fecha . ' + 1 month'));
        return date('Y-m-05', strtotime($fecha));
    }
}
