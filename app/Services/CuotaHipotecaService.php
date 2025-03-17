<?php

namespace App\Services;

use App\Models\Prestamo_Hipotecario;
use App\Models\Pago;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;

class CuotaHipotecaService extends CuotaService
{

    use Loggable;

    private $cuentaInternaService;

    public function __construct(CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;
    }

    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario)
    {
        $plazo = $prestamoHipotecario->plazo;
        $plazo = $this->calcularPlazo($plazo, $prestamoHipotecario->tipo_plazo);

        $cuota = $this->calcularCuota($prestamoHipotecario->monto, $prestamoHipotecario->interes,  $plazo);

        $this->generarCuotas($prestamoHipotecario, $cuota, $plazo);
    }

    private function generarCuotas(Prestamo_Hipotecario $prestamoHipotecario, $cuota, $plazo)
    {

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
        $pago->interes = $this->calcularInteres($pagoAnterior ? $pagoAnterior->saldo : $prestamo->monto,  $this->calcularTaza($prestamo->interes));
        $pago->capital = $cuota - $pago->interes;
        $pago->fecha = $this->obtenerFechaSiguienteMes($pagoAnterior->fecha);
        $pago->saldo = ($pagoAnterior ? $pagoAnterior->saldo : $prestamo->monto) - $pago->capital;
        $pago->realizado = false;
        $pago->id_pago_anterior = $pagoAnterior ? $pagoAnterior->id : null;
        $pago->save();
        return $pago;
    }


    private function generarPagoInvalido($prestamo)
    {
        $fecha = $prestamo->fecha_inicio;
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->capital = 0;
        $pago->interes = $this->calcularInteres($this->calcularInteresDiario($prestamo->interes,  $fecha), $prestamo->monto);
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

    public function realizarPago($data, $id)
    {
        DB::beginTransaction();
        $pago = $this->getPago($id);
        $prestamo = $pago->prestamo; // Ensure 'prestamo' is a relationship returning a Prestamo_Hipotecario instance
        if ($pago->capital > $data['capital']) {
            throw new \Exception('El capital pagado no puede ser menor al capital de la cuota');
        }

        $this->crearPago($data, $pago);

        $dataCuenta = [
            'ingreso' => $data['monto'],
            'egreso' => 0,
            'descripcion' => 'Pago de cuota de hipoteca con id ' . $pago->id . ' del prestamo con id ' . $prestamo->id . '  con fecha ' . $pago->fecha_pago . ' por un monto de ' . $data['monto'] . ' y un capital de ' . $data['capital'] . ' con un saldo de ' . $pago->saldo . ' y un interes de ' . $pago->interes,
        ];
        $this->cuentaInternaService->createCuenta($dataCuenta);


        if ($data['capital'] > $pago->capital && $pago->saldo > 0 && $pago->pagoSiguiente()) {
            $this->actualizarSiguentesPago($pago, $prestamo->first());
        }
        DB::commit();
    }

    private function crearPago($data, $pago)
    {
        $pago->fecha_pago = date('Y-m-d');
        $pago->realizado = true;
        $pago->monto_pagado = $data['monto'];
        $pago->capital_pagado = $data['capital'];
        $pago->saldo = $pago->saldo - $pago->capital_pagado;
        $pago->no_documento = $data['no_documento'];
        $pago->tipo_documento = $data['tipo_documento'];
        $pago->fecha_documento = $data['fecha_documento'];
        $pago->save();
    }

    private function actualizarSiguentesPago(Pago $pago, Prestamo_Hipotecario $prestamoHipotecario)
    {
        $pagoSiguiente = $pago->pagoSiguiente();
        $pagoSiguiente->interes = $this->calcularInteres($pago->saldo,  $this->calcularTaza($prestamoHipotecario->interes));
        $pagoSiguiente->capital = $pago->saldo - $pagoSiguiente->interes;
        $pagoSiguiente->saldo = $pago->saldo - $pagoSiguiente->capital;
        $pagoSiguiente->save();
        if ($pagoSiguiente->saldo > 0) {

            if ($pagoSiguiente->pagoSiguiente()) {
                $this->actualizarSiguentesPago($pagoSiguiente, $prestamoHipotecario);
            } else {
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
        $this->log('El dia es ' . $dia);
        return $dia >= 1 && $dia <= 5;
    }

    private function calcularCuota($monto, $interes, $plazo)
    {
        $tasaInteresMensual = $this->calcularTaza($interes);
        $numeroPagos = $plazo;
        $this->log('La tasa de interes mensual es ' . $tasaInteresMensual);
        $this->log('El numero de pagos es ' . $numeroPagos);

        return ($monto * $tasaInteresMensual) / (1 - pow(1 + $tasaInteresMensual, -$numeroPagos));
    }

    private function calcularInteres($monto, $taza)
    {
        return $monto * $taza;
    }

    private function calcularInteresDiario($interes, $fecha)
    {

        $diasFaltantes = $this->calcularDiasFaltantes($fecha);
        $diasDelMes = $this->obtenerDiasDelMes($fecha, 0);
        $interes = $interes / $diasDelMes;
        return  $this->calcularTaza($interes) * $diasFaltantes;
    }

    private function calcularDiasFaltantes($fecha)
    {
        $fechaActual = $fecha;
        $fechaSiguiente = $this->obtenerFechaSiguienteMes($fecha);
        $diferencia = $fechaActual->diff($fechaSiguiente);
        return $diferencia->format("%a");
    }
    private function obtenerFechaSiguienteMes($fecha)
    {
        $fecha = date('Y-m-d', strtotime($fecha . ' + 1 month'));
        return date('Y-m-05', strtotime($fecha));
    }
}
