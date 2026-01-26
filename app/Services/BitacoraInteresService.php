<?php

namespace App\Services;

use App\Models\Pago;
use App\Models\Prestamo_Hipotecario;
use App\Models\Historico_Saldo;
use App\Traits\ErrorHandler;
use App\Traits\Loggable;
use DateTime;

class BitacoraInteresService
{
    use Loggable;
    use ErrorHandler;

    public function registrarHistoricoSaldo(Prestamo_Hipotecario $prestamo, $nuevoSaldo, $fecha)
    {
        $historico = new Historico_Saldo();
        $historico->saldo = $nuevoSaldo;
        $historico->interes_pagado = 0;
        $historico->prestamo_hipotecario_id = $prestamo->id;
        $historico->created_at = $fecha;
        $historico->save();

        $this->log("Histórico de saldo registrado para el préstamo ID {$prestamo->id} con saldo {$historico->saldo} e interés pagado {$historico->interes_pagado}");
    }

    public function obtenerUltimoHistorico(Prestamo_Hipotecario $prestamo)
    {
        return Historico_Saldo::where('prestamo_hipotecario_id', $prestamo->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }


    public function calcularInteresPendiente(Pago $pago, $fechaPago)
    {
        $prestamo = Prestamo_Hipotecario::find($pago->id_prestamo);
        if (!$prestamo) {
            throw new \Exception("Préstamo no encontrado para el pago ID {$pago->id}");
        }

        $ultimoHistorico = $this->obtenerUltimoHistorico($prestamo);
        if (!$ultimoHistorico) {
            throw new \Exception("No se encontró histórico de saldo para el préstamo ID {$prestamo->id}");
        }

        $saldo = $ultimoHistorico->saldo;
        $tasaInteresAnual = $prestamo->interes;
        $fechaUltimoPago = new DateTime($ultimoHistorico->created_at);
        $fechaPagoObj = new DateTime($fechaPago);
        if ($fechaPagoObj < $fechaUltimoPago) {
            $this->lanzarExcepcionConCodigo("La fecha de pago no puede ser anterior al último registro de histórico");
        }
        $fechaPago = new DateTime($pago->fecha);

        $interesTotal = $this->calcularInteres($fechaPago, $saldo, $tasaInteresAnual, $fechaUltimoPago, $fechaPagoObj);

        $interesAPagar = $interesTotal - $ultimoHistorico->interes_pagado;
        return [
            'id_historico' => $ultimoHistorico->id,
            'interes_pendiente' => round($interesAPagar, 2)
        ];
    }

    private function calcularInteresNormal($saldo, $interesMensual, $fechaInicio, $fechaFin)
    {
        $diasTranscurridos = $fechaFin->diff($fechaInicio)->days;
        // Usar el año de la FECHA FIN para determinar bisiesto (fecha en que se cobra el interés)
        $anio = (int)$fechaFin->format('Y');
        $esBisiesto = ($anio % 4 === 0 && ($anio % 100 !== 0 || $anio % 400 === 0));
        $diasDelAnio = $esBisiesto ? 366 : 365;
        $interesDiario = $saldo * (($interesMensual * 12 / 100) / $diasDelAnio);
        return $interesDiario * $diasTranscurridos;
    }


    public function calcularInteres(DateTime $fechaPago, $saldo, $interesMensual, DateTime $fechaUltimoPago, DateTime $fechaDeposito)
    {

        $fechaLimiteStr = date('Y-m-10', strtotime($fechaPago->format('Y-m-d')));
        $fechaLimite = new DateTime($fechaLimiteStr);
        $this->log("Fecha de pago: " . $fechaPago->format('Y-m-d') . ", Fecha límite: " . $fechaLimite->format('Y-m-d'));
        // Caso 1: Pago después de la fecha límite (genera mora)
        if ($fechaDeposito > $fechaLimite) {
            $fechaPagoMasUnMes = (clone $fechaPago)->modify('+1 month');
            $interesTotal = $this->calcularInteres($fechaPagoMasUnMes, $saldo, $interesMensual, $fechaUltimoPago, $fechaDeposito);
        } else if ($fechaDeposito > $fechaPago) {
            // Caso 2: Pago en la misma fecha del pago programado (sin interés)
            $interesTotal = $this->calcularInteresNormal($saldo, $interesMensual, $fechaUltimoPago, $fechaPago);
        } else {
            // Caso 3:  Pago dentro del plazo normal
            $interesTotal = $this->calcularInteresNormal($saldo, $interesMensual, $fechaUltimoPago, $fechaDeposito);
        }
        return $interesTotal;
    }

    public function actualizarInteresPagado($idHistorico, $montoPagado)
    {
        $historico = Historico_Saldo::find($idHistorico);
        if (!$historico) {
            throw new \Exception("Histórico de saldo no encontrado con ID {$idHistorico}");
        }

        $historico->interes_pagado += $montoPagado;
        $historico->save();

        $this->log("Interés pagado actualizado en el histórico ID {$idHistorico}. Nuevo interés pagado: {$historico->interes_pagado}");
    }
}
