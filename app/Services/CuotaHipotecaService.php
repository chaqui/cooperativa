<?php

namespace App\Services;

use App\Constants\FrecuenciaPago;
use App\Models\Prestamo_Hipotecario;
use App\Models\Pago;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use PDO;

class CuotaHipotecaService extends CuotaService
{

    use Loggable;


    private DepositoService $depositoService;

    public function __construct(DepositoService $depositoService)
    {
        $this->depositoService = $depositoService;
    }

    /**
     * Calcula las cuotas para un préstamo hipotecario y genera los pagos correspondientes
     *
     * @param Prestamo_Hipotecario $prestamoHipotecario Préstamo a procesar
     * @return Prestamo_Hipotecario Préstamo con la cuota calculada y pagos generados
     * @throws \Exception Si ocurre un error durante el cálculo o generación de cuotas
     */
    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario)
    {
        // Calcular el plazo efectivo según el tipo (meses, años, etc.)
        $plazoEfectivo = $this->calcularPlazo(
            $prestamoHipotecario->plazo,
            $prestamoHipotecario->tipo_plazo
        );
        $this->log("Plazo efectivo calculado: {$plazoEfectivo} meses");

        // Calcular el valor de la cuota mensual
        $cuotaMensual = $this->calcularCuota(
            $prestamoHipotecario->monto,
            $prestamoHipotecario->interes,
            $plazoEfectivo
        );
        $this->log("Cuota mensual calculada: Q{$cuotaMensual}");

        // Actualizar la cuota del préstamo
        $prestamoHipotecario->cuota = $cuotaMensual;
        $prestamoHipotecario->save();

        // Generar los pagos mensuales
        $this->generarCuotas($prestamoHipotecario, $plazoEfectivo);
        $this->log("Pagos generados correctamente para {$plazoEfectivo} meses");
        return $prestamoHipotecario;
    }


    public function getPago(string $id): Pago
    {
        return Pago::findOrFail($id);
    }

    public function getPagos(Prestamo_Hipotecario $prestamoHipotecario)
    {
        return Pago::where('id_prestamo', $prestamoHipotecario->id)->get();
    }

    /**
     * Realiza el pago de una cuota hipotecaria
     * @param mixed $data Información del pago con las siguientes claves:
     *        - monto: (requerido) Monto a pagar
     *       - tipo_documento: Tipo de documento que respalda el pago
     *       - no_documento:  Número del documento
     *      - imagen: (opcional) URL o ruta de la imagen del comprobante
     *      - tipo_cuenta_interna_id: ID del tipo de cuenta interna
     *      - fecha: Fecha del pago
     *      - id_pago: ID del pago asociado
     *
     * @param mixed $id ID del pago a realizar
     * @return void
     */
    public function realizarPago($data, $id)
    {
        $this->validarPago($data);
        DB::beginTransaction();
        try {
            $pago = $this->getPago($id);


            $this->validarEstadoPago($pago);
            $montoOriginal = $data['monto'];
            $montoRestante = $montoOriginal;
            $detallesPago = [
                'interesGanado' => 0,
                'capitalGanado' => 0,
                'descripcion' => '',
            ];
            //validacion de penalizacion
            $montoRestante = $this->procesarPenalizacion($pago, $montoRestante, $detallesPago);
            $montoRestante = $this->procesarIntereses($pago, $montoRestante, $detallesPago, $data['fecha_documento']);
            $montoRestante = $this->procesarCapital($pago, $montoRestante, $detallesPago);


            $pago->monto_pagado += $montoOriginal;

            if ($montoRestante > 0) {
                $detallesPago['descripcion'] .= "Se abonó la cantidad de Q.{$montoRestante} a capital; ";
                $this->actualizarSiguentesPago($pago, $pago->saldo, $montoRestante);
            }

            // Verificar si el pago está completo
            if ($pago->saldoFaltante() <= 0) {
                //$pago->realizado = true;
                $pago->nuevo_saldo = $pago->saldo - ($pago->capital_pagado - $pago->capital);
                $this->actualizarSiguentesPago($pago,  $pago->nuevo_saldo);
            }
            $pago->fecha_pago = $data['fecha_documento'];
            $pago->save();

            $this->registrarDepositoYTransaccion($data, $pago, $detallesPago);

            DB::commit();
        } catch (\Exception $e) {
            $this->logError('Error al realizar el pago: ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }

    private function validarPago($data){
        $this->log('Validando pago');
        if (!isset($data['monto']) || $data['monto'] <= 0) {
            throw new \InvalidArgumentException('El monto es requerido y debe ser mayor que cero');
        }
        if (!isset($data['tipo_documento']) || empty($data['tipo_documento'])) {
            throw new \InvalidArgumentException('El tipo de documento es requerido');
        }
        if (!isset($data['no_documento']) || empty($data['no_documento'])) {
            throw new \InvalidArgumentException('El número de documento es requerido');
        }
        if (!isset($data['fecha_documento']) || empty($data['fecha_documento'])) {
            throw new \InvalidArgumentException('La fecha del documento es requerida');
        }

        if (new \DateTime($data['fecha_documento']) > new \DateTime()) {
            throw new \InvalidArgumentException('La fecha del documento no puede ser mayor a la fecha actual');
        }
    }

    public function obtenerDepositos($id)
    {
        $pago = $this->getPago($id);
        return  $pago->depositos;
    }

    private function generarCuotas(Prestamo_Hipotecario $prestamoHipotecario,  $plazo)
    {
        $this->eliminarPagosExistentes($prestamoHipotecario);
        $pagoAnterior = null;

        if (!$this->esFechaValida($prestamoHipotecario->fecha_inicio)) {
            $this->log("Fecha de inicio {$prestamoHipotecario->fecha_inicio} no es válida, generando pago parcial inicial");
            $pagoAnterior = $this->generarPagoInvalido($prestamoHipotecario);
        }

        for ($i = 0; $i < $plazo; $i++) {
            $numeroCuota = $i + 1;
            $this->log("Generando cuota #{$numeroCuota} de {$plazo}");
            $pagoAnterior = $this->generarPago(
                $prestamoHipotecario->cuota,
                $pagoAnterior,
                $prestamoHipotecario
            );
        }
    }

    /**
     * Elimina los pagos existentes para un préstamo si los hay
     * Útil cuando se recalculan las cuotas
     *
     * @param Prestamo_Hipotecario $prestamoHipotecario
     * @return int Número de pagos eliminados
     */
    private function eliminarPagosExistentes(Prestamo_Hipotecario $prestamoHipotecario)
    {
        // Solo eliminar pagos que no hayan sido realizados
        $pagosExistentes = Pago::where('id_prestamo', $prestamoHipotecario->id)
            ->where('realizado', false)
            ->count();

        if ($pagosExistentes > 0) {
            $this->log("Eliminando {$pagosExistentes} pagos existentes no realizados para recalcular");

            Pago::where('id_prestamo', $prestamoHipotecario->id)
                ->where('realizado', false)
                ->delete();
        }

        return $pagosExistentes;
    }

    private function generarPago($cuota,  $pagoAnterior, $prestamo)
    {
        // Determinar el saldo base y la fecha base
        $saldoBase = $pagoAnterior ? $pagoAnterior->saldo : $prestamo->monto;
        $fechaBase = $pagoAnterior ? $pagoAnterior->fecha : $prestamo->fecha_inicio;
        // Validar datos
        if ($cuota <= 0) {
            throw new \InvalidArgumentException("La cuota debe ser mayor que cero");
        }

        if ($saldoBase <= 0) {
            throw new \InvalidArgumentException("El saldo base debe ser mayor que cero");
        }

        // Calcular componentes del pago
        $tasaInteresMensual = $this->calcularTaza($prestamo->interes);
        $interesMensual = $this->calcularInteres($saldoBase, $tasaInteresMensual);

        // Crear y configurar el objeto pago
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->interes = $interesMensual;
        $pago->fecha = $this->obtenerFechaSiguienteMes($fechaBase);
        $pago->numero_pago_prestamo = $pagoAnterior ? $pagoAnterior->numero_pago_prestamo + 1 : 1;
        $pago->realizado = false;
        $pago->id_pago_anterior = $pagoAnterior ? $pagoAnterior->id : null;

        // Información adicional útil
        $pago->interes_pagado = 0;
        $pago->capital_pagado = 0;
        $pago->monto_pagado = 0;
        $pago->penalizacion = 0;
        $pago->recargo = 0;
        $pago->fecha_pago = null;

        // Calcular el capital mensual
        $capitalMensual = $this->calcularCapital($pago, $prestamo, $saldoBase);
        $this->log("Capital mensual calculado: Q{$capitalMensual}");
        $nuevoSaldo = $saldoBase - $capitalMensual;

        // Si el saldo resultante es muy pequeño (por errores de redondeo), ajustarlo a cero
        if ($nuevoSaldo < 0.01) {
            $nuevoSaldo = 0;
            $capitalMensual = $saldoBase;
        }
        $pago->capital = $capitalMensual;
        $pago->saldo = $nuevoSaldo;
        // Registrar la creación del pago
        $pago->save();

        $this->log("Pago generado con éxito: ID {$pago->id}, Capital: {$pago->capital}, Interés: {$pago->interes}, Saldo: {$pago->saldo}");

        return $pago;
    }

    private function calcularCapital($pago, $prestamo, $saldoBase)
    {
        $this->log("Calculando capital para el pago #{$pago->numero_pago_prestamo}");

        if ($prestamo->frecuencia_pago == FrecuenciaPago::$MENSUAL) {
            return $prestamo->cuota - $pago->interes;
        }
        $this->log("La frecuencia de Pago a Capital es " . $prestamo->frecuenciaPago());
        // Si el número de pago es múltiplo de la frecuencia de pago, calcular el capital
        if (($pago->numero_pago_prestamo % $prestamo->frecuenciaPago()) == 0) {
            $this->log("El número de pago es múltiplo de la frecuencia de pago");

            $plazo = $this->calcularPlazo($prestamo->plazo, $prestamo->tipo_plazo) / $prestamo->frecuenciaPago();
            $actual = $pago->numero_pago_prestamo / $prestamo->frecuenciaPago();
            $cuotas =  (($plazo - $actual) + 1);
            return $saldoBase / $cuotas;
        }
        return 0;
    }


    private function generarPagoInvalido($prestamo)
    {
        $this->log("Generando pago parcial inicial para préstamo #{$prestamo->id}");

        $fecha = $prestamo->fecha_inicio;

        // Validar fecha
        if (!$fecha) {
            throw new \InvalidArgumentException("La fecha de inicio del préstamo es requerida");
        }

        // Calcular el interés diario y multiplicarlo por los días restantes del mes
        $diasRestantes = $this->calcularDiasFaltantes($fecha);
        $tasaInteresDiaria = $this->calcularInteresDiario($prestamo->interes, $fecha);
        $interesAcumulado = $prestamo->monto * $tasaInteresDiaria;

        $this->log("Días restantes hasta próximo mes: {$diasRestantes}, Interés acumulado: {$interesAcumulado}");

        // Crear el registro de pago inicial
        $pago = new Pago();
        $pago->id_prestamo = $prestamo->id;
        $pago->capital = 0; // No se amortiza capital en este pago parcial
        $pago->interes = $interesAcumulado;
        $pago->fecha = $this->obtenerFechaSiguienteMes($fecha);
        $pago->numero_pago_prestamo = 0; // Número de pago inicial
        $pago->saldo = $prestamo->monto; // El saldo se mantiene igual
        $pago->realizado = false;

        // Inicializar campos adicionales
        $pago->interes_pagado = 0;
        $pago->capital_pagado = 0;
        $pago->monto_pagado = 0;
        $pago->penalizacion = 0;
        $pago->recargo = 0;

        $pago->save();

        $this->log("Pago parcial inicial generado con éxito: ID {$pago->id}, Interés: {$pago->interes}");

        return $pago;
    }


    /**
     * Valida el estado del pago antes de procesarlo
     */
    private function validarEstadoPago($pago)
    {

        // Validar que los pagos anteriores estén realizados
        $pagoAnterior = $pago->pagoAnterior();
        if ($pagoAnterior && !$pagoAnterior->realizado) {
            throw new \Exception('No se puede realizar este pago porque el pago anterior no ha sido completado.');
        }

        // Validar que el pago no haya sido realizado
        if ($pago->realizado) {
            throw new \Exception('El pago ya ha sido realizado');
        }

        // Validar que el saldo sea mayor que cero
        if ($pago->saldo <= 0) {
            $pago->realizado = true;
            $pago->save();
            DB::commit();
            throw new \Exception('El saldo ya es cero');
        }
    }

    /**
     * Procesa el pago de penalización si existe
     */
    private function procesarPenalizacion($pago, $montoDisponible, &$detallesPago)
    {
        if ($pago->penalizacion <= 0 || $montoDisponible <= 0) {
            return $montoDisponible;
        }

        $penalizacionPendiente = $pago->penalizacion - $pago->recargo;

        if ($penalizacionPendiente <= 0) {
            return $montoDisponible;
        }

        $montoPenalizacion = min($montoDisponible, $penalizacionPendiente);
        $pago->recargo += $montoPenalizacion;

        $detallesPago['interesGanado'] += $montoPenalizacion;
        $detallesPago['descripcion'] .= "Se abonó por penalización la cantidad de Q.{$montoPenalizacion}; ";

        return $montoDisponible - $montoPenalizacion;
    }


    /**
     * Procesa el pago de intereses
     */
    private function procesarIntereses($pago, $montoDisponible, &$detallesPago, $fechaPago)
    {
        // Validar que el monto disponible sea mayor que cero
        if ($montoDisponible <= 0) {
            return 0;
        }

        // Validar que el interés sea mayor que cero
        if ($pago->interes <= 0) {
            return $montoDisponible;
        }

        // Obtener los días del mes
        $diasDelMes = $this->obtenerDiasDelMes($pago->fecha, 0);
        $this->log("Días del mes: {$diasDelMes}");

        // Calcular los días acumulados desde la fecha del pago
        $diasAcumulados = $this->obtenerDiasAcumulados($fechaPago);
        $this->log("Días acumulados desde la ultima fecha de pago: {$diasAcumulados}");

        // Si no hay días acumulados, no se puede procesar el interés
        if ($diasAcumulados <= 0) {
            return $montoDisponible;
        }

        // Calcular el interés diario
        $interesAPagar = $pago->interes / $diasDelMes;
        $interesAPagar = $interesAPagar * $diasAcumulados;
        $this->log("Interés a pagar: Q{$interesAPagar}");

        $interesPendiente =  $interesAPagar  - $pago->interes_pagado;
        $this->log("Interés pendiente: Q{$interesPendiente}");
        // Si el interés pendiente es menor o igual a cero, no se puede procesar
        if ($interesPendiente <= 0) {
            return $montoDisponible;
        }

        // Calcular el monto a pagar por intereses
        $montoInteres = min($montoDisponible, $interesPendiente);
        $this->log("Monto a pagar por intereses: Q{$montoInteres}");
        // Actualizar el pago
        $pago->interes_pagado += $montoInteres;

        $detallesPago['interesGanado'] += $montoInteres;
        $detallesPago['descripcion'] .= "Se abonó por intereses la cantidad de Q.{$montoInteres}; ";

        return $montoDisponible - $montoInteres;
    }

    /**
     * Procesa el pago al capital
     */
    private function procesarCapital($pago, $montoDisponible, &$detallesPago)
    {
        if ($montoDisponible <= 0) {
            return 0;
        }
        if ($pago->capital <= 0) {
            return $montoDisponible;
        }

        $pago->capital_pagado += $montoDisponible;

        $detallesPago['capitalGanado'] += $montoDisponible;
        $detallesPago['descripcion'] .= "Se abonó a capital la cantidad de Q.{$montoDisponible}";

        return 0;
    }

    private function pagarCapital(Pago $pago, $monto): string
    {
        $this->log("Pagando capital para el pago #{$pago->id}");
        if ($monto <= 0) {
            return '';
        }

        $detallesPago = [
            'capitalGanado' => 0,
            'interesGanado' => 0,
            'descripcion' =>  "",
        ];

        $montoRestante = $this->procesarCapital($pago, $monto, $detallesPago);

        if ($montoRestante <= 0) {
            $this->log("El monto restante es cero, actualizando el saldo");
            $pago->nuevo_saldo = $pago->saldo - ($pago->capital_pagado - $pago->capital);
            $detallesPago['descripcion']  = $detallesPago['descripcion'] . $this->actualizarSiguentesPago($pago,  $pago->nuevo_saldo);
        }
        $pago->save();
        return $detallesPago['descripcion'];
    }


    private function actualizarSiguentesPago(Pago $pago, $nuevoSaldo, $montoExtra = 0): string
    {
        $descripcion = '';
        $prestamoHipotecario = $pago->prestamo;
        $pagoSiguiente = $pago->pagoSiguiente();

        // Si no hay un siguiente pago, detener el proceso
        if (!$pagoSiguiente) {
            return $descripcion;
        }
        $this->log("Actualizando siguiente pago: ID {$pagoSiguiente->id}");

        // Si hay un monto extra, procesarlo en el siguiente pago
        if ($montoExtra > 0) {
            $this->log("Monto extra a procesar: Q{$montoExtra}");
            if ($pagoSiguiente->numero_pago_prestamo % $prestamoHipotecario->frecuenciaPago() == 0
            ) {
                $this->log("El pago es múltiplo de la frecuencia de pago");
                $descripcion = $this->pagarCapital($pagoSiguiente, $montoExtra);
            } else {
                $descripcion = $descripcion . $this->actualizarSiguentesPago($pagoSiguiente, 0, $montoExtra);
            }
            return $descripcion;
        }
        // Calcular el interés y el capital del siguiente pago
        $pagoSiguiente->interes = $this->calcularInteres($nuevoSaldo, $this->calcularTaza($prestamoHipotecario->interes));
        $capital = $this->calcularCapital($pagoSiguiente, $prestamoHipotecario, $nuevoSaldo);

        // Ajustar el capital si el saldo restante es menor
        if ($nuevoSaldo < $capital) {
            $capital = $nuevoSaldo;
        }

        $pagoSiguiente->capital = $capital;
        $pagoSiguiente->saldo = $nuevoSaldo - $pagoSiguiente->capital;

        // Guardar los cambios en el siguiente pago
        $pagoSiguiente->save();

        $this->log("Pago siguiente actualizado: ID {$pagoSiguiente->id}, Capital: {$pagoSiguiente->capital}, Interés: {$pagoSiguiente->interes}, Saldo: {$pagoSiguiente->saldo}");

        // Si el saldo del siguiente pago es mayor a cero, continuar con los pagos siguientes
        if ($pagoSiguiente->saldo > 0) {
            $descripcion = $descripcion . $this->actualizarSiguentesPago($pagoSiguiente, $pagoSiguiente->saldo);
        } else {
            // Si el saldo es cero, eliminar los pagos restantes
            $pagoProximo = $pagoSiguiente->pagoSiguiente();
            $this->eliminarPago($pagoProximo);
        }
        return $descripcion;
    }

    /**
     * Elimina un pago y todos sus pagos siguientes de forma recursiva
     *
     * @param Pago|null $pago Pago a eliminar
     * @param int $maxNivel Nivel máximo de recursión para prevenir desbordamiento de pila (por defecto 50)
     * @param int $nivelActual Nivel actual de recursión (uso interno)
     * @return int Número de pagos eliminados
     * @throws \Exception Si el pago ya ha sido realizado o la recursión excede el límite
     */
    private function eliminarPago(?Pago $pago, int $maxNivel = 50, int $nivelActual = 0): int
    {
        // Validar que exista el pago
        if (!$pago) {
            $this->log("No hay pago para eliminar");
            return 0;
        }

        // Protección contra recursión excesiva
        if ($nivelActual >= $maxNivel) {
            $this->logError("Se alcanzó el límite de recursión ({$maxNivel}) al eliminar pagos");
            throw new \Exception("Profundidad de recursión excesiva al eliminar pagos");
        }

        // Verificar que el pago no esté realizado
        if ($pago->realizado) {
            $this->logError("Intento de eliminar pago realizado #{$pago->id}");
            throw new \Exception("No se puede eliminar un pago ya realizado (#{$pago->id})");
        }



        $pagosEliminados = 1; // Contamos este pago
        $this->log("Eliminando pago #{$pago->id} (nivel {$nivelActual})");

        // Procesar pagos siguientes primero (para mantener integridad referencial)
        $pagoSiguiente = $pago->pagoSiguiente();
        if ($pagoSiguiente) {
            $pagosEliminados += $this->eliminarPago(
                $pagoSiguiente,
                $maxNivel,
                $nivelActual + 1
            );
        }

        // Guardar información para logs
        $prestamoId = $pago->id_prestamo;
        $pagoId = $pago->id;
        $fechaPago = $pago->fecha;

        // Eliminar el pago actual
        $pago->delete();

        $this->log("Pago #{$pagoId} del préstamo #{$prestamoId} (fecha: {$fechaPago}) eliminado correctamente");

        // Confirmar transacción si iniciamos una

        $this->log("Transacción completada: {$pagosEliminados} pagos eliminados en total");


        return $pagosEliminados;
    }


    /**
     * Registra el depósito y la transacción en la cuenta interna
     */
    private function registrarDepositoYTransaccion($data, $pago, $detallesPago)
    {
        $descripcion = $detallesPago['descripcion'] . ' del pago #' . $pago->id .
            ' del préstamo #' . $pago->id_prestamo .
            ' codigo del préstamo ' . $pago->prestamo->codigo .
            ' fecha ' . now();
        // Crear depósito
        $this->depositoService->crearDeposito([
            'tipo_documento' => $data['tipo_documento'],
            'id_pago' => $pago->id,
            'monto' => $data['monto'],
            'numero_documento' => $data['no_documento'],
            'imagen' => $data['imagen'] ?? null,
            'capital' => $detallesPago['capitalGanado'],
            'interes' => $detallesPago['interesGanado'],
            'motivo' => $descripcion,
            'id_cuenta' => $data['id_cuenta'],
        ]);
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

        // Evitar división por cero o cálculos incorrectos
        if ($tasaInteresMensual <= 0 || $plazo <= 0) {
            throw new \InvalidArgumentException("La tasa de interés y el plazo deben ser mayores a cero");
        }

        // Fórmula de amortización francesa: C = P * (r * (1 + r)^n) / ((1 + r)^n - 1)
        $cuota = ($monto * $tasaInteresMensual) / (1 - pow(1 + $tasaInteresMensual, -$plazo));

        // Redondear a 2 decimales para evitar problemas de precisión
        return round($cuota, 2);
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

    private function obtenerFechaAnterior($fecha)
    {
        $dia = date('j', strtotime($fecha));
        if ($dia <= 5) {
            return date('Y-m-05', strtotime($fecha . ' - 1 month'));
        }
        return date('Y-m-05', strtotime($fecha));
    }

    private function obtenerDiasAcumulados($fecha)
    {
        $this->log('La fecha es ' . $fecha);
        $fechaAnterior = $this->obtenerFechaAnterior($fecha);
        $this->log('La fecha anterior es ' . $fechaAnterior);
        $diferencia = (new \DateTime($fechaAnterior))->diff(new \DateTime($fecha));
        return $diferencia->format("%a");
    }
}
