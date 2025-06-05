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
    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario, $cuotaPagada = 0)
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
        $this->generarCuotas($prestamoHipotecario, $plazoEfectivo, $cuotaPagada);
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
            $this->log('Iniciando proceso de pago de cuota No.' . $pago->numero_pago_prestamo);

            $this->validarEstadoPago($pago);
            $montoOriginal = $data['monto'];
            $montoRestante = $montoOriginal;
            $detallesPago = [
                'interesGanado' => 0,
                'capitalGanado' => 0,
                'descripcion' => '',
            ];

            $montoRestante = $this->procesarPenalizacion($pago, $montoRestante, $detallesPago);
            $montoRestante = $this->procesarIntereses($pago, $montoRestante, $detallesPago, $data['fecha_documento']);
            $montoRestante = $this->procesarCapital($pago, $montoRestante, $detallesPago);


            $pago->monto_pagado += $montoOriginal;

            // Verificar si el pago está completo
            if ($pago->capitalFaltante() <= 0) {

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

    private function registrarPagoExistente($pago)
    {
        $this->log("El pago {$pago->numero_pago_prestamo} ya ha sido pagado, registrando como realizado");
        $montoOriginal = $pago->interes + $pago->capital + $pago->penalizacion;
        $montoRestante = $montoOriginal;
        $detallesPago = [
            'interesGanado' => 0,
            'capitalGanado' => 0,
            'descripcion' => '',
        ];
        $montoRestante = $this->procesarPenalizacion($pago, $montoRestante, $detallesPago);
        $montoRestante = $this->procesarIntereses($pago, $montoRestante, $detallesPago, $pago->fecha);
        $montoRestante = $this->procesarCapital($pago, $montoRestante, $detallesPago);
        $pago->monto_pagado += $montoOriginal;
        $pago->fecha_pago = $pago->fecha;
        $pago->realizado = true;
        $pago->save();
    }

    private function validarPago($data)
    {
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

    private function generarCuotas(Prestamo_Hipotecario $prestamoHipotecario,  $plazo, $cuotaPagada = 0)
    {
        $this->eliminarPagosExistentes($prestamoHipotecario);
        $pagoAnterior = null;

        if (!$this->esFechaValida($prestamoHipotecario->fecha_inicio)) {
            $this->log("Fecha de inicio {$prestamoHipotecario->fecha_inicio} no es válida, generando pago parcial inicial");
            $pagoAnterior = $this->generarPagoInvalido($prestamoHipotecario, $cuotaPagada);
        }

        for ($i = 0; $i < $plazo; $i++) {
            $numeroCuota = $i + 1;
            if($prestamoHipotecario->existente && $pagoAnterior && $pagoAnterior->saldo <=0) {
                $this->log("El saldo del pago anterior es cero, no se generará más cuotas");
                continue;
            }
            $this->log("Generando cuota #{$numeroCuota} de {$plazo}");
            $pagoAnterior = $this->generarPago(
                $pagoAnterior,
                $prestamoHipotecario,
                $plazo,
                $cuotaPagada
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

    /**
     * Genera un pago para un préstamo hipotecario
     *
     * @param Pago|null $pagoAnterior Información del pago anterior
     * @param Prestamo_Hipotecario $prestamo Información del préstamo
     * @param int $plazo Plazo del préstamo en meses
     * @param int $cuotaPagada Número de cuotas ya pagadas
     * @return Pago Pago generado
     * @throws \InvalidArgumentException Si los datos proporcionados no son válidos
     */
    private function generarPago(?Pago $pagoAnterior, Prestamo_Hipotecario $prestamo, int $plazo, int $cuotaPagada): Pago
    {
        $this->log("Iniciando generación de pago para el préstamo #{$prestamo->id}");

        // Validar datos de entrada
        $this->validarDatosPago($prestamo, $plazo);

        // Determinar el saldo base y la fecha base
        $saldoBase = $this->obtenerSaldoBase($prestamo, $pagoAnterior, $cuotaPagada);

        $fechaBase = $pagoAnterior ? $pagoAnterior->fecha : $prestamo->fecha_inicio;
        $this->log("Saldo base: Q{$saldoBase}, Fecha base: {$fechaBase}");

        // Calcular componentes del pago
        $tasaInteresMensual = $this->calcularTaza($prestamo->interes);
        $this->log("Tasa de interés mensual: {$tasaInteresMensual}");
        $interesMensual = $this->calcularInteres($saldoBase, $tasaInteresMensual);
        $this->log("Interés mensual calculado: Q{$interesMensual}");
        $capitalMensual = $this->calcularCapital($interesMensual, $prestamo, $saldoBase, $plazo, $pagoAnterior);
        $this->log("Capital mensual calculado: Q{$capitalMensual}");
        if ($prestamo->existente && $cuotaPagada > 0 && $prestamo->tieneCuotaInvalida()) {
            $cuotaPagada = $cuotaPagada - 1;
        }

        // Ajustar el saldo y el capital si es necesario
        $nuevoSaldo = $this->calcularNuevoSaldo(
            $prestamo,
            $pagoAnterior,
            $saldoBase,
            $capitalMensual,
            $cuotaPagada
        );
        if ($nuevoSaldo < 0.01) {
            $nuevoSaldo = 0;
            $capitalMensual = $saldoBase;
        }

        // Crear y configurar el objeto Pago
        $pago = $this->crearPago(
            $prestamo,
            $pagoAnterior,
            $interesMensual,
            $capitalMensual,
            $nuevoSaldo,
            $fechaBase,
            $cuotaPagada
        );

        if ($pago->numero_pago_prestamo <= $cuotaPagada) {
            $this->registrarPagoExistente($pago);
        }

        // Registrar la fecha final del préstamo si es el último pago
        if ($pago->numero_pago_prestamo == $plazo) {
            $this->log("El pago {$pago->numero_pago_prestamo} es el último pago del préstamo");
            $this->registrarFechaFinalPrestamo($prestamo, $pago);
        }

        $this->log("Pago generado con éxito: ID {$pago->id}, Capital: {$pago->capital}, Interés: {$pago->interes}, Saldo: {$pago->saldo}");
        return $pago;
    }

    /**
     * Calcula el nuevo saldo del préstamo después de un pago
     *
     * @param Prestamo_Hipotecario $prestamo Información del préstamo
     * @param Pago|null $pagoAnterior Información del pago anterior
     * @param float $saldoBase Saldo base del préstamo
     * @param float $capitalMensual Capital mensual a pagar
     * @param int $cuotaPagada Número de cuotas ya pagadas
     * @return float Nuevo saldo del préstamo
     */
    private function calcularNuevoSaldo($prestamo, $pagoAnterior, $saldoBase, $capitalMensual, $cuotaPagada)
    {
        if ($prestamo->existente && $pagoAnterior !== null && $pagoAnterior->numero_pago_prestamo == $cuotaPagada - 1) {
            return $prestamo->saldo_existente;
        }
        return max(0, $saldoBase - $capitalMensual);
    }

    /**
     * Valida los datos necesarios para generar un pago
     *
     * @param Prestamo_Hipotecario $prestamo
     * @param int $plazo
     * @throws \InvalidArgumentException
     */
    private function validarDatosPago(Prestamo_Hipotecario $prestamo, int $plazo): void
    {
        if ($prestamo->cuota <= 0) {
            throw new \InvalidArgumentException("La cuota debe ser mayor que cero");
        }

        if ($plazo <= 0) {
            throw new \InvalidArgumentException("El plazo debe ser mayor que cero");
        }
    }

    /**
     * Crea un objeto Pago y lo guarda en la base de datos
     *
     * @param Prestamo_Hipotecario $prestamo
     * @param Pago|null $pagoAnterior
     * @param float $interesMensual
     * @param float $capitalMensual
     * @param float $nuevoSaldo
     * @param string $fechaBase
     * @param int $cuotaPagada
     * @return Pago
     */
    private function crearPago(
        Prestamo_Hipotecario $prestamo,
        ?Pago $pagoAnterior,
        float $interesMensual,
        float $capitalMensual,
        float $nuevoSaldo,
        string $fechaBase,
        int $cuotaPagada
    ): Pago {
        $pago =  Pago::generarPago(
            $prestamo,
            $interesMensual,
            $capitalMensual,
            $nuevoSaldo,
            $cuotaPagada,
            $this->obtenerFechaSiguienteMes($fechaBase, true),
            $pagoAnterior
        );

        $pago->save();
        return $pago;
    }

    /**
     *
     * Función para registrar la fecha final del préstamo
     * @param mixed $prestamo prestamo
     * @param mixed $pago ultimo pago
     * @return void
     */
    private function registrarFechaFinalPrestamo($prestamo, $pago)
    {
        $this->log("El pago " . $pago->numero_pago_prestamo . " es el último pago del préstamo");
        $this->log("Actualizando fecha de finalización del préstamo a {$pago->fecha}");
        $prestamo->fecha_fin = $pago->fecha;
        $prestamo->save();
        $this->log("Fecha de finalización del préstamo actualizada a {$prestamo->fecha_fin}");
    }

    /**
     *
     * Función para obtener el saldo base del préstamo
     * @param mixed $prestamo prestamo
     * @param mixed $pagoAnterior pago anterior
     * @param mixed $cuotaPagada cuota pagada
     * @throws \InvalidArgumentException
     */
    private function obtenerSaldoBase($prestamo, $pagoAnterior, $cuotaPagada)
    {
        $this->log("Obteniendo saldo base para el préstamo #{$prestamo->id}");
        $this->log(" El pago anterior es: " . ($pagoAnterior ? $pagoAnterior->numero_pago_prestamo : 'N/A'));

        $saldoBase = $pagoAnterior ? $pagoAnterior->saldo : $prestamo->monto;

        $this->log("Saldo base calculado: Q{$saldoBase}");


        return $saldoBase;
    }

    /**
     * Calcula el capital a pagar en función del pago, préstamo y saldo base
     * @param mixed $pago información del pago
     * @param mixed $prestamo información del préstamo
     * @param mixed $saldoBase saldo base del préstamo
     * @return float|int Capital a pagar
     */
    private function calcularCapital($interes, $prestamo, $saldoBase, $plazo, $pagoAnterior)
    {


        // Determinar el número de pago actual
        $numeroPago = $pagoAnterior ? $pagoAnterior->numero_pago_prestamo + 1 : 1;
        $this->log("Número de pago: {$numeroPago}");

        $frecuenciaPago = $prestamo->frecuencia_pago;
        $this->log("La frecuencia de Pago a Capital es " . $frecuenciaPago);

        // Caso 1: Frecuencia de pago mensual
        if ($frecuenciaPago == FrecuenciaPago::$MENSUAL) {
            $this->log("Calculando capital para frecuencia mensual");
            $capital = floatval($prestamo->cuota) - floatval($interes);
            $this->log("Capital calculado para frecuencia mensual: Q{$capital}");
            return max(0, $capital);
        }

        // Caso 2: Frecuencia de pago única
        if ($frecuenciaPago == FrecuenciaPago::$UNICA) {
            if ($plazo == $numeroPago) {
                $this->log("Último pago, capital igual al saldo base: Q{$saldoBase}");
                return $saldoBase;
            }
            $this->log("Pago único, capital calculado: Q0");
            return 0;
        }


        $frecuenciaPagoCantidad = $prestamo->frecuenciaPago();
        $this->log("Las cuotas de frecuencia de pago son: {$frecuenciaPagoCantidad}");

        // Caso 3: Frecuencia de pago personalizada
        if (($numeroPago  % $frecuenciaPagoCantidad) == 0) {
            $this->log("El número de pago es múltiplo de la frecuencia de pago");
            $plazo = $plazo / $frecuenciaPagoCantidad;
            $actual = ($numeroPago + 1) / $frecuenciaPagoCantidad;
            $cuotasRestantes =  (($plazo - $actual) + 1);

            $capital = $saldoBase / $cuotasRestantes;
            $this->log("Capital calculado para frecuencia personalizada: Q{$capital}");
            return round($capital, 2); // Redondear a 2 decimales
        }

        // Caso 4: No es múltiplo de la frecuencia de pago
        $this->log("El número de pago no es múltiplo de la frecuencia de pago, capital calculado: Q0");
        return 0;
    }


    /**
     * Genera un pago parcial inicial para un préstamo hipotecario
     *
     * @param Prestamo_Hipotecario $prestamo Préstamo hipotecario
     * @return Pago Pago generado
     * @throws \InvalidArgumentException Si la fecha de inicio del préstamo no es válida
     */
    private function generarPagoInvalido($prestamo, $cuotaPagada)
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
        $fecha = $this->obtenerFechaSiguienteMes($fecha, true);
        $pago = Pago::generarPagoInvalido(
            $prestamo,
            $interesAcumulado,
            $fecha
        );

        $pago->save();

        if ($prestamo->existente && $cuotaPagada > 0) {
            $this->registrarPagoExistente($pago);
        }


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
            $this->log("No hay penalización a pagar o monto disponible es cero");
            return $montoDisponible;
        }

        $this->log("Procesando penalización: {$pago->penalizacion}");

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
            $this->log("No hay monto disponible para procesar intereses");
            return 0;
        }

        // Validar que el interés sea mayor que cero
        if ($pago->interes <= 0) {
            $this->log("No hay interés a pagar");
            return $montoDisponible;
        }
        $this->log("Procesando intereses: {$pago->interes}");

        // Calcular los días del mes paara el interes
        $diasDelMes = $pago->numero_pago_prestamo == 0
            ? $this->calcularDiasFaltantes($pago->prestamo->fecha_inicio)
            : $this->obtenerDiasDelMes($pago->fecha, 0);

        $this->log("Días del mes para Interes: {$diasDelMes}");
        // Calcular los días acumulados
        $diasAcumulados = $fechaPago < $pago->fecha ?
            ($pago->numero_pago_prestamo == 0
                ? $diasDelMes - $this->calcularDiasFaltantes($fechaPago)
                : $this->obtenerDiasAcumulados($fechaPago))
            : $diasDelMes;

        $this->log("Días acumulados de interes para este mes: {$diasAcumulados}");

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
            $this->log("No hay monto disponible para procesar capital");
            return 0;
        }
        $this->log("Procesando capital: {$pago->capital}");
        $pago->capital_pagado += $montoDisponible;

        $detallesPago['capitalGanado'] += $montoDisponible;
        $detallesPago['descripcion'] .= "Se abonó a capital la cantidad de Q.{$montoDisponible}";

        return 0;
    }

    /**
     * Actualiza los pagos siguientes después de realizar un pago
     *
     * @param Pago $pago Pago actual
     * @param float $nuevoSaldo Nuevo saldo del préstamo
     * @return string Descripción de los cambios realizados
     */
    private function actualizarSiguentesPago(Pago $pago, $nuevoSaldo): string
    {
        $descripcion = '';
        $prestamoHipotecario = $pago->prestamo;
        $pagoSiguiente = $pago->pagoSiguiente();

        // Si no hay un siguiente pago, detener el proceso
        if (!$pagoSiguiente) {
            return $descripcion;
        }
        $this->log("Actualizando siguiente pago: ID {$pagoSiguiente->id}");

        // Calcular el interés y el capital del siguiente pago
        $plazo = $this->calcularPlazo(
            $prestamoHipotecario->plazo,
            $prestamoHipotecario->tipo_plazo
        );
        $pagoSiguiente->interes = $this->calcularInteres($nuevoSaldo, $this->calcularTaza($prestamoHipotecario->interes));
        $capital = $this->calcularCapital($pagoSiguiente->interes, $prestamoHipotecario, $nuevoSaldo, $plazo, $pago);

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
        if ($pagoSiguiente->numero_pago_prestamo == $plazo) {
            $prestamoHipotecario->fecha_fin = $pagoSiguiente->fecha;
            $prestamoHipotecario->save();
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
        $this->log('La fecha es ' . $fecha);
        $fechaActual = new \DateTime($fecha);
        $fechaSiguiente = $this->obtenerFechaSiguienteMes($fecha);
        $this->log('La fecha siguiente es ' . $fechaSiguiente);
        $fechaSiguiente = new \DateTime($fechaSiguiente);
        $diferencia = $fechaActual->diff($fechaSiguiente);
        return $diferencia->format("%a") + 1;
    }
    private function obtenerFechaSiguienteMes($fecha, $nuevo = false)
    {
        $this->log('La fecha es ' . $fecha);
        $dia = date('j', strtotime($fecha));

        if ($nuevo || $dia > 5) {
            return date('Y-m-05', strtotime($fecha . ' + 1 month'));
        }
        $this->log('El dia es menor a 5 y no es para nuevo pago');
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
