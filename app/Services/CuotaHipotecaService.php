<?php

namespace App\Services;

use App\Constants\FrecuenciaPago;
use App\Models\Prestamo_Hipotecario;
use App\Models\Pago;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Services\DepositoService;
use App\Traits\ErrorHandler;

class CuotaHipotecaService extends CuotaService
{
    use ErrorHandler;

    use Loggable;


    private DepositoService $depositoService;

    protected $tipoCuentaInternaService;

    private BitacoraInteresService $bitacoraInteresService;



    public function __construct(DepositoService $depositoService, TipoCuentaInternaService $tipoCuentaInternaService, BitacoraInteresService $bitacoraInteresService)
    {
        $this->depositoService = $depositoService;
        $this->tipoCuentaInternaService = $tipoCuentaInternaService;
        $this->bitacoraInteresService = $bitacoraInteresService;
    }

    /**
     * Calcula las cuotas para un préstamo hipotecario y genera los pagos correspondientes
     *
     * @param Prestamo_Hipotecario $prestamoHipotecario Préstamo a procesar
     * @return Prestamo_Hipotecario Préstamo con la cuota calculada y pagos generados
     * @throws \Exception Si ocurre un error durante el cálculo o generación de cuotas
     */
    /**
     * Calcula las cuotas para un préstamo hipotecario y genera los pagos correspondientes
     *
     * @param Prestamo_Hipotecario $prestamoHipotecario Préstamo a procesar
     * @param int $cuotaPagada Número de cuotas ya pagadas
     * @return Prestamo_Hipotecario Préstamo con la cuota calculada y pagos generados
     * @throws \Exception Si ocurre un error durante el cálculo o generación de cuotas
     */
    public function calcularCuotas(Prestamo_Hipotecario $prestamoHipotecario)
    {
        try {
            // Validar datos del préstamo
            $this->validarDatosPrestamoCalculoCuotas($prestamoHipotecario);

            // Calcular el plazo efectivo según el tipo (meses, años, etc.)
            $plazoEfectivo = $this->calcularPlazo(
                $prestamoHipotecario->plazo,
                $prestamoHipotecario->tipo_plazo
            );

            // Calcular el valor de la cuota mensual
            $cuotaMensual = $this->calcularCuota(
                $prestamoHipotecario->monto,
                $prestamoHipotecario->interes,
                $plazoEfectivo
            );

            // Actualizar la cuota del préstamo
            $prestamoHipotecario->cuota = $cuotaMensual;
            $prestamoHipotecario->save();

            // Generar los pagos mensuales
            $this->generarCuotas($prestamoHipotecario, $plazoEfectivo);

            // Verificar y ajustar amortizaciones para garantizar exactitud
            $this->verificarYAjustarAmortizaciones($prestamoHipotecario);

            $this->log("Cuotas generadas: {$plazoEfectivo} pagos, cuota mensual: Q{$cuotaMensual}");

            return $prestamoHipotecario;
        } catch (\Exception $e) {
            $this->manejarError($e, 'calcularCuotas');
            return $prestamoHipotecario; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Valida los datos del préstamo antes de calcular cuotas
     *
     * @param Prestamo_Hipotecario $prestamo
     * @throws \Exception Si algún dato es inválido
     */
    private function validarDatosPrestamoCalculoCuotas(Prestamo_Hipotecario $prestamo): void
    {
        if (!$prestamo->id) {
            $this->lanzarExcepcionConCodigo("El préstamo debe estar guardado antes de calcular cuotas");
        }
        if ($prestamo->monto <= 0) {
            $this->lanzarExcepcionConCodigo("El monto del préstamo debe ser mayor a cero");
        }
        if ($prestamo->interes < 0) {
            $this->lanzarExcepcionConCodigo("La tasa de interés no puede ser negativa");
        }
        if ($prestamo->plazo <= 0) {
            $this->lanzarExcepcionConCodigo("El plazo del préstamo debe ser mayor a cero");
        }
        if (empty($prestamo->fecha_inicio)) {
            $this->lanzarExcepcionConCodigo("La fecha de inicio del préstamo es requerida");
        }
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
            $this->procesarPago($pago, $data, $pago->prestamo);
            DB::commit();
        } catch (\Exception $e) {
            $this->logError('Error al realizar el pago: ' . $e->getMessage());
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Calcula la penalización por retraso de forma estandarizada
     * 
     * @param Pago $pago Cuota a evaluar
     * @param string $fechaPago Fecha del pago
     * @return float Monto de penalización
     */
    private function calcularPenalizacionPorRetraso($pago, $fechaPago)
    {
        if (!$pago || !$pago->fecha_vencimiento) {
            $this->log("⚠️ No hay pago o fecha de vencimiento para calcular penalización");
            return 0;
        }

        $fechaVencimiento = new \DateTime($pago->fecha_vencimiento);
        $fechaActualPago = new \DateTime($fechaPago);
        
        // Si no hay retraso, no hay penalización
        if ($fechaActualPago <= $fechaVencimiento) {
            $this->log("✅ Pago a tiempo - No hay penalización");
            return 0;
        }
        
        // Calcular días de retraso
        $diasRetraso = $fechaActualPago->diff($fechaVencimiento)->days;
        $this->log("📅 Días de retraso: {$diasRetraso}");
        
        // Penalización estándar: 3% del valor de la cuota por mes de retraso
        $tasaPenalizacionMensual = 0.03; // 3% mensual
        $mesesRetraso = ceil($diasRetraso / 30); // Redondear hacia arriba
        
        $penalizacion = $pago->valor_cuota * $tasaPenalizacionMensual * $mesesRetraso;
        
        $this->log("💰 Cálculo de penalización:");
        $this->log("   - Valor cuota: Q{$pago->valor_cuota}");
        $this->log("   - Meses de retraso: {$mesesRetraso}");
        $this->log("   - Tasa penalización mensual: {$tasaPenalizacionMensual}%");
        $this->log("   - Penalización calculada: Q{$penalizacion}");
        
        return round($penalizacion, 2);
    }

    public function registrarPagoExistente($prestamo, $deposito)
    {
        $pago = $prestamo->cuotaActiva();
        return $this->procesarPago($pago, $deposito, $prestamo, true);
    }


    private function procesarPago($pago, $deposito, $prestamo, $existente = false){

        $montoOriginal = $deposito['monto'];
        $fechaPago = $deposito['fecha_documento'];
        $penalizacionUsuario = isset($deposito['penalizacion']) ? $deposito['penalizacion'] : null;
        
        $this->log("Registrando depósito Q{$montoOriginal} - Pago #{$pago->numero_pago_prestamo}");
        
        // NUEVA VALIDACIÓN: Verificar monto mínimo requerido (solo para pagos nuevos)
        if (!$existente) {
            $validacionMonto = $this->validarMontoMinimoRequerido($pago, $montoOriginal, $fechaPago, $penalizacionUsuario);
            $this->log("✅ Validación de monto completada - Excedente para capital: Q{$validacionMonto['excedente']}");
        }
        
        $montoRestante = $montoOriginal;
        $detallesPago = [
            'interesGanado' => 0,
            'capitalGanado' => 0,
            'descripcion' => '',
            'penalizacion' => 0
        ];

        // Obtener el saldo actual correcto para múltiples depósitos
        $saldoActual = $this->obtenerSaldoActualPago($pago);

        $montoRestante = $existente ? $this->procesarPenalizacionExistente($pago, $montoRestante, $detallesPago, $deposito['penalizacion'])
        : $this->procesarPenalizacionUsuario($pago, $montoRestante, $detallesPago, $fechaPago, $penalizacionUsuario);
        $montoRestante = $this->procesarIntereses($pago, $montoRestante, $detallesPago, $deposito['fecha_documento']);
        $montoRestante = $this->procesarCapital($pago, $montoRestante, $detallesPago);
        $pago->monto_pagado += $montoOriginal;

        // Calcular correctamente el nuevo saldo después del pago real
        if ($detallesPago['capitalGanado'] > 0) {
            $nuevoSaldoCalculado = $saldoActual - $detallesPago['capitalGanado'];
            $pago->nuevo_saldo = $nuevoSaldoCalculado;

            $this->log("Capital: Q{$detallesPago['capitalGanado']} - Saldo: Q{$nuevoSaldoCalculado}");
            $this->bitacoraInteresService->registrarHistoricoSaldo($pago->prestamo, $nuevoSaldoCalculado, $deposito['fecha_documento']);

            // Solo actualizar pagos siguientes si es necesario (evitar múltiples actualizaciones)
            $this->actualizarSiguentesPagoSiEsNecesario($pago, $nuevoSaldoCalculado);

            // Verificar y ajustar amortizaciones después de actualizar pagos siguientes
            $this->verificarYAjustarAmortizaciones($prestamo);
        }

        if ($existente && $pago->capitalFaltante() <= 0) {
            $pago->realizado = true;
            $this->log("Pago #{$pago->numero_pago_prestamo} completado");
        }

        $fechaLimite = \Carbon\Carbon::parse($pago->fecha)->addDays(5);
        if ($deposito['fecha_documento'] > $fechaLimite) {
            $this->actualizarFechas($pago->pagoSiguiente(), $deposito['fecha_documento']);
        }

        $pago->fecha_pago = $deposito['fecha_documento'];
        $pago->save();

        $data = [
            'monto' => $montoOriginal,
            'tipo_documento' => $deposito['tipo_documento'],
            'no_documento' => $existente ? $deposito['numero_documento'] : $deposito['no_documento'],
            'fecha_documento' => $deposito['fecha_documento'],
            'id_cuenta' => $this->tipoCuentaInternaService->getCuentaParaDepositosAnteriores()->id,
            'existente' => true
        ];
        $this->registrarDepositoYTransaccion($data, $pago, $detallesPago);

        // Actualizar fecha final del préstamo después del pago
        $this->actualizarFechaFinalPrestamo($prestamo);

        return $pago->nuevo_saldo;
    }

    /**
     * Obtiene el saldo actual correcto del pago, considerando múltiples depósitos
     *
     * @param object $pago Pago actual
     * @return float Saldo actual del pago
     */
    private function obtenerSaldoActualPago($pago)
    {
        try {
            // Si ya hay un nuevo_saldo (de depósitos anteriores), usar ese
            if ($pago->nuevo_saldo > 0) {
                return $pago->nuevo_saldo;
            }

            // Si no hay nuevo_saldo, calcular el saldo original
            $saldoOriginal = $pago->saldo + $pago->capital;
            return $saldoOriginal;
        } catch (\Exception $e) {
            $this->manejarError($e, 'obtenerSaldoActualPago');
            return 0; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Actualiza pagos siguientes solo si es necesario para evitar múltiples actualizaciones
     *
     * @param object $pago Pago actual
     * @param float $nuevoSaldo Nuevo saldo calculado
     */
    private function actualizarSiguentesPagoSiEsNecesario($pago, $nuevoSaldo)
    {
        try {
            $pagoSiguiente = $pago->pagoSiguiente();

            // Solo actualizar si hay un pago siguiente y el saldo cambió significativamente
            if ($pagoSiguiente) {
                $diferenciaSaldo = abs($pagoSiguiente->saldo - ($nuevoSaldo - $pagoSiguiente->capital));

                // Si la diferencia es mayor a 0.01 (1 centavo), actualizar
                if ($diferenciaSaldo > 0.01) {
                    $this->log("Actualizando pagos siguientes");
                    $this->actualizarSiguentesPago($pago, $nuevoSaldo);
                }
            }
        } catch (\Exception $e) {
            $this->manejarError($e, 'actualizarSiguentesPagoSiEsNecesario');
        }
    }


    /**
     * Actualiza la fecha de pago y la fecha del pago en el modelo Pago.
     *
     * @param Pago $pago
     * @param string $nuevaFecha
     * @return void
     */
    private function actualizarFechas($pago, $fechaDeposito)
    {
        $diasDeposito = (new \DateTime($fechaDeposito))->format('d');
        if ($diasDeposito > 10) {
            $nuevaFecha = (new \DateTime($fechaDeposito))->modify('+1 month')->format('Y-m-d');
            $nuevaFecha = date('Y-m-05', strtotime($nuevaFecha));
        } else {
            $nuevaFecha = (new \DateTime($fechaDeposito))->format('Y-m-05');
        }

        $this->log("Actualizando fechas del pago #{$pago->id} a {$nuevaFecha}");
        $pago->fecha = $nuevaFecha;
        $pago->save();
        if ($pago->pagoSiguiente()) {
            $nuevaFecha = (new \DateTime($pago->fecha))->modify('+1 month')->format('Y-m-05');
            $this->actualizarFechas($pago->pagoSiguiente(), $nuevaFecha);
        }
    }

    private function calcularNuevaFecha($pago, $fechaDeposito)
    {
        $diasDeposito = (new \DateTime($fechaDeposito))->format('d');
        if ($diasDeposito > 10) {
            $nuevaFecha = (new \DateTime($fechaDeposito))->modify('+1 month')->format('Y-m-d');
            $nuevaFecha = date('Y-m-05', strtotime($nuevaFecha));
        } else {
            $nuevaFecha = (new \DateTime($fechaDeposito))->format('Y-m-05');
        }

        $this->log("Actualizando fechas del pago #{$pago->id} a {$nuevaFecha}");
        return $nuevaFecha;
    }



    private function validarPago($data)
    {
        $this->log('Validando pago');
        if (!isset($data['monto']) || $data['monto'] <= 0) {
            $this->lanzarExcepcionConCodigo("El monto es requerido y debe ser mayor que cero");
        }
        if (!isset($data['tipo_documento']) || empty($data['tipo_documento'])) {
            $this->lanzarExcepcionConCodigo("El tipo de documento es requerido");
        }
        if (!isset($data['no_documento']) || empty($data['no_documento'])) {
            $this->lanzarExcepcionConCodigo("El número de documento es requerido");
        }
        if (!isset($data['fecha_documento']) || empty($data['fecha_documento'])) {
            $this->lanzarExcepcionConCodigo("La fecha del documento es requerida");
        }

        if (new \DateTime($data['fecha_documento']) > new \DateTime()) {
            $this->lanzarExcepcionConCodigo("La fecha del documento no puede ser mayor a la fecha actual");
        }
    }

    /**
     * Valida que el monto del pago sea suficiente para cubrir intereses y penalizaciones
     * 
     * @param Pago $pago Pago a validar
     * @param float $montoPago Monto del pago
     * @param string $fechaPago Fecha del pago
     * @param float $penalizacionUsuario Penalización ingresada por el usuario (opcional)
     * @return array Información detallada de la validación
     * @throws \Exception Si el pago es insuficiente
     */
    private function validarMontoMinimoRequerido($pago, $montoPago, $fechaPago, $penalizacionUsuario = null)
    {
        $this->log("🔍 Validando monto mínimo requerido para pago #{$pago->numero_pago_prestamo}");
        
        // Calcular interés pendiente
        $respuestaInteres = $this->bitacoraInteresService->calcularInteresPendiente($pago, $fechaPago);
        $interesPendiente = $respuestaInteres['interes_pendiente'];
        
        // Usar penalización del usuario si está disponible, sino calcular automáticamente
        if ($penalizacionUsuario !== null && $penalizacionUsuario >= 0) {
            $penalizacion = $penalizacionUsuario;
            $this->log("💰 Usando penalización ingresada por usuario: Q{$penalizacion}");
        } else {
            $penalizacion = $this->calcularPenalizacionPorRetraso($pago, $fechaPago);
            $this->log("🧮 Penalización calculada automáticamente: Q{$penalizacion}");
        }
        
        // Calcular monto mínimo requerido
        $montoMinimoRequerido = $interesPendiente + $penalizacion;
        
        $detallesValidacion = [
            'interes_pendiente' => round($interesPendiente, 2),
            'penalizacion' => round($penalizacion, 2),
            'monto_minimo_requerido' => round($montoMinimoRequerido, 2),
            'monto_pagado' => round($montoPago, 2),
            'deficit' => round(max(0, $montoMinimoRequerido - $montoPago), 2),
            'excedente' => round(max(0, $montoPago - $montoMinimoRequerido), 2),
            'es_suficiente' => $montoPago >= $montoMinimoRequerido
        ];
        
        $this->log("💰 Interés pendiente: Q{$detallesValidacion['interes_pendiente']}");
        $this->log("⚠️ Penalización: Q{$detallesValidacion['penalizacion']}");
        $this->log("📋 Monto mínimo requerido: Q{$detallesValidacion['monto_minimo_requerido']}");
        $this->log("💵 Monto pagado: Q{$detallesValidacion['monto_pagado']}");
        
        if (!$detallesValidacion['es_suficiente']) {
            $this->log("❌ PAGO INSUFICIENTE - Déficit: Q{$detallesValidacion['deficit']}");
            $this->lanzarExcepcionConCodigo(
                "El monto del pago (Q{$detallesValidacion['monto_pagado']}) es insuficiente. " .
                "Monto mínimo requerido: Q{$detallesValidacion['monto_minimo_requerido']} " .
                "(Interés: Q{$detallesValidacion['interes_pendiente']} + Penalización: Q{$detallesValidacion['penalizacion']})"
            );
        }
        
        $this->log("✅ Pago suficiente - Excedente para capital: Q{$detallesValidacion['excedente']}");
        return $detallesValidacion;
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
            if ($pagoAnterior && $pagoAnterior->saldo <= 0) {
                $this->log("El saldo del pago anterior es cero, no se generará más cuotas");
                continue;
            }
            $this->log("Generando cuota #{$numeroCuota} de {$plazo}");
            $pagoAnterior = $this->generarPago(
                $pagoAnterior,
                $prestamoHipotecario,
                $plazo
            );
            $this->log("Cuota #{$numeroCuota} generada con ID: {$pagoAnterior->id}");
        }

        // Calcular y mostrar la amortización total
        $pagosGenerados = Pago::where('id_prestamo', $prestamoHipotecario->id)->get();
        $amortizacionTotal = $pagosGenerados->sum('capital');
        $this->log("Amortización total calculada: Q{$amortizacionTotal}");

        // Actualizar la fecha final del préstamo basándose en la última cuota generada
        $this->actualizarFechaFinalPrestamo($prestamoHipotecario);
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
    private function  generarPago(?Pago $pagoAnterior, Prestamo_Hipotecario $prestamo, int $plazo): Pago
    {
        $this->log('Pago anterior: ' . ($pagoAnterior ? $pagoAnterior->id : "N/A"));
        $this->log("Iniciando generación de pago para el préstamo #{$prestamo->id}");

        // Validar datos de entrada
        $this->validarDatosPago($prestamo, $plazo);

        // Determinar el saldo base y la fecha base
        $saldoBase = $this->obtenerSaldoBase($prestamo, $pagoAnterior);

        $fechaBase = $pagoAnterior ? $pagoAnterior->fecha : $prestamo->fecha_inicio;
        $this->log("Saldo base: Q{$saldoBase}, Fecha base: {$fechaBase}");

        // Calcular componentes del pago
        $tasaInteresMensual = $this->calcularTaza($prestamo->interes);
        $this->log("Tasa de interés mensual: {$tasaInteresMensual}");
        $interesMensual = $this->calcularInteres($saldoBase, $tasaInteresMensual);
        $this->log("Interés mensual calculado: Q{$interesMensual}");
        $capitalMensual = $this->calcularCapital($interesMensual, $prestamo, $saldoBase, $plazo, $pagoAnterior);
        $this->log("Capital mensual calculado: Q{$capitalMensual}");

        // Ajustar el saldo y el capital si es necesario
        $nuevoSaldo = max(0, $saldoBase - $capitalMensual);
        $this->log("Nuevo saldo después del pago: Q{$nuevoSaldo}");

        // Determinar si es la última cuota del préstamo
        $numeroPago = $pagoAnterior ? $pagoAnterior->numero_pago_prestamo + 1 : 1;
        $esUltimaCuota = ($numeroPago == $plazo);
        $this->log("Es última cuota: " . ($esUltimaCuota ? "Sí" : "No"));

        // Para la última cuota, ajustar el capital exactamente al saldo restante
        // para evitar diferencias por redondeo acumulativo
        if ($esUltimaCuota || $nuevoSaldo < 0.01) {
            $this->log("Ajustando última cuota - Saldo restante: Q{$saldoBase}");
            $nuevoSaldo = 0;
            $capitalMensual = $saldoBase; // Capital exacto = saldo restante
            $this->log("Capital ajustado en última cuota: Q{$capitalMensual}");
        }


        // Crear y configurar el objeto Pago
        $pago = $this->crearPago(
            $prestamo,
            $pagoAnterior,
            $interesMensual,
            $capitalMensual,
            $nuevoSaldo,
            $fechaBase
        );



        // Nota: La fecha final del préstamo se actualiza en generarCuotas()
        // para asegurar que siempre refleje la última cuota activa
        if ($pago->numero_pago_prestamo == $plazo) {
            $this->log("El pago {$pago->numero_pago_prestamo} es el último pago del préstamo");
        }

        $this->log("Pago generado con éxito: ID {$pago->id}, Capital: {$pago->capital}, Interés: {$pago->interes}, Saldo: {$pago->saldo}");
        return $pago;
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
            $this->lanzarExcepcionConCodigo("La cuota debe ser mayor que cero");
        }

        if ($plazo <= 0) {
            $this->lanzarExcepcionConCodigo("El plazo debe ser mayor que cero");
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
    ): Pago {
        $pago =  Pago::generarPago(
            $prestamo,
            $interesMensual,
            $capitalMensual,
            $nuevoSaldo,
            $this->obtenerFechaSiguienteMes($fechaBase, true),
            $pagoAnterior
        );

        $pago->save();
        return $pago;
    }

    /**
     * Actualiza la fecha final del préstamo basándose en la última cuota activa
     *
     * @param Prestamo_Hipotecario $prestamo Préstamo a actualizar
     * @return void
     */
    private function actualizarFechaFinalPrestamo(Prestamo_Hipotecario $prestamo)
    {
        try {
            $this->log("Actualizando fecha final del préstamo #{$prestamo->id}");

            // Buscar la última cuota (pago) activa del préstamo por fecha (no por número)
            $ultimoPago = Pago::where('id_prestamo', $prestamo->id)
                ->orderBy('fecha', 'desc')
                ->orderBy('numero_pago_prestamo', 'desc') // En caso de empate de fechas
                ->first();

            if (!$ultimoPago) {
                $this->log("No se encontraron pagos para el préstamo, no se actualiza fecha_fin");
                return;
            }

            $this->log("Última cuota encontrada: #{$ultimoPago->numero_pago_prestamo} con fecha {$ultimoPago->fecha}");

            // Actualizar la fecha final solo si es diferente
            if ($prestamo->fecha_fin !== $ultimoPago->fecha) {
                $fechaAnterior = $prestamo->fecha_fin ?? 'null';
                $prestamo->fecha_fin = $ultimoPago->fecha;
                $prestamo->save();

                $this->log("Fecha final actualizada de {$fechaAnterior} a {$prestamo->fecha_fin}");
            } else {
                $this->log("Fecha final ya está correcta: {$prestamo->fecha_fin}");
            }
        } catch (\Exception $e) {
            $this->manejarError($e, 'actualizarFechaFinalPrestamo');
        }
    }

    /**
     *
     * Función para registrar la fecha final del préstamo
     * @param mixed $prestamo prestamo
     * @param mixed $pago ultimo pago
     * @return void
     * @deprecated Use actualizarFechaFinalPrestamo() instead
     */
    private function registrarFechaFinalPrestamo($prestamo, $pago)
    {
        $this->log("El pago " . $pago->numero_pago_prestamo . " es el último pago del préstamo");
        $this->log("Actualizando fecha de finalización del préstamo a {$pago->fecha}");
        $prestamo->fecha_fin_nueva = $pago->fecha;
        $prestamo->save();
        $this->log("Fecha de finalización del préstamo actualizada a {$prestamo->fecha_fin_nueva}");
    }

    /**
     *
     * Función para obtener el saldo base del préstamo
     * @param mixed $prestamo prestamo
     * @param mixed $pagoAnterior pago anterior
     * @param mixed $cuotaPagada cuota pagada
     * @throws \InvalidArgumentException
     */
    private function obtenerSaldoBase($prestamo, $pagoAnterior)
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

            // Asegurar que el capital no sea negativo
            $capital = max(0, $capital);

            // Para evitar errores de redondeo, redondear el capital
            $capital = round($capital, 2);

            $this->log("Capital calculado para frecuencia mensual: Q{$capital}");
            return $capital;
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
    private function generarPagoInvalido($prestamo)
    {
        $this->log("Generando pago parcial inicial para préstamo #{$prestamo->id}");

        $fecha = $prestamo->fecha_inicio;

        // Validar fecha
        if (!$fecha) {
            $this->lanzarExcepcionConCodigo("La fecha de inicio del préstamo es requerida");
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
            $this->lanzarExcepcionConCodigo("No se puede realizar este pago porque el pago anterior no ha sido completado.");
        }

        // Validar que el pago no haya sido realizado
        if ($pago->realizado) {
            $this->lanzarExcepcionConCodigo("El pago ya ha sido realizado");
        }

        // Validar que el saldo sea mayor que cero
        if ($pago->saldo <= 0) {
            $pago->realizado = true;
            $pago->save();
            DB::commit();
            $this->lanzarExcepcionConCodigo("El saldo ya es cero");
        }
    }

    /**
     * Procesa la penalización usando el valor ingresado por el usuario o calculado automáticamente
     * 
     * @param Pago $pago Cuota a procesar
     * @param float $montoDisponible Monto disponible para el pago
     * @param array $detallesPago Array de detalles del pago (por referencia)
     * @param string $fechaPago Fecha del pago
     * @param float|null $penalizacionUsuario Penalización ingresada por el usuario
     * @return float Monto restante después de aplicar penalización
     */
    private function procesarPenalizacionUsuario($pago, $montoDisponible, &$detallesPago, $fechaPago, $penalizacionUsuario = null)
    {
        if ($montoDisponible <= 0) {
            $this->log("No hay monto disponible para procesar penalización");
            return $montoDisponible;
        }

        // Determinar la penalización a usar
        if ($penalizacionUsuario !== null && $penalizacionUsuario >= 0) {
            $penalizacionTotal = $penalizacionUsuario;
            $this->log("💰 Usando penalización ingresada por usuario: Q{$penalizacionTotal}");
            
            // Actualizar el pago con la penalización del usuario
            $pago->penalizacion = $penalizacionTotal;
        } else {
            // Si no hay penalización del usuario, usar la existente o calcular
            if ($pago->penalizacion > 0) {
                $penalizacionTotal = $pago->penalizacion;
                $this->log("📋 Usando penalización existente en el pago: Q{$penalizacionTotal}");
            } else {
                $penalizacionTotal = $this->calcularPenalizacionPorRetraso($pago, $fechaPago);
                $this->log("🧮 Penalización calculada automáticamente: Q{$penalizacionTotal}");
                $pago->penalizacion = $penalizacionTotal;
            }
        }
        
        if ($penalizacionTotal <= 0) {
            $this->log("No hay penalización que procesar");
            return $montoDisponible;
        }

        // Verificar si ya se ha pagado parte de la penalización
        $penalizacionPendiente = $penalizacionTotal - $pago->recargo;
        
        if ($penalizacionPendiente <= 0) {
            $this->log("Penalización ya pagada completamente");
            return $montoDisponible;
        }

        // Aplicar penalización con el monto disponible
        $montoPenalizacion = min($montoDisponible, $penalizacionPendiente);
        $pago->recargo += $montoPenalizacion;

        $origenPenalizacion = ($penalizacionUsuario !== null) ? "ingresada por usuario" : "calculada automáticamente";
        $detallesPago['descripcion'] .= "Se abonó por penalización ({$origenPenalizacion}) Q{$montoPenalizacion} de Q{$penalizacionTotal} total; ";
        $detallesPago['penalizacion'] += $montoPenalizacion;
        
        $this->log("✅ Penalización procesada: Q{$montoPenalizacion} de Q{$penalizacionPendiente} pendiente");
        
        return $montoDisponible - $montoPenalizacion;
    }

    /**
     * Procesa la penalización utilizando el cálculo estandarizado
     * 
     * @param Pago $pago Cuota a procesar
     * @param float $montoDisponible Monto disponible para el pago
     * @param array $detallesPago Array de detalles del pago (por referencia)
     * @param string $fechaPago Fecha del pago
     * @return float Monto restante después de aplicar penalización
     */
    private function procesarPenalizacionEstandarizada($pago, $montoDisponible, &$detallesPago, $fechaPago)
    {
        if ($montoDisponible <= 0) {
            $this->log("No hay monto disponible para procesar penalización");
            return $montoDisponible;
        }

        // Calcular penalización estandarizada basada en retraso real
        $penalizacionCalculada = $this->calcularPenalizacionPorRetraso($pago, $fechaPago);
        
        if ($penalizacionCalculada <= 0) {
            $this->log("No hay penalización por retraso");
            return $montoDisponible;
        }

        // Verificar si ya se ha pagado parte de la penalización
        $penalizacionPendiente = $penalizacionCalculada - $pago->recargo;
        
        if ($penalizacionPendiente <= 0) {
            $this->log("Penalización ya pagada completamente");
            return $montoDisponible;
        }

        // Aplicar penalización con el monto disponible
        $montoPenalizacion = min($montoDisponible, $penalizacionPendiente);
        $pago->recargo += $montoPenalizacion;
        
        // Actualizar penalización en el pago si es necesario
        if ($pago->penalizacion < $penalizacionCalculada) {
            $this->log("Actualizando penalización en pago de Q{$pago->penalizacion} a Q{$penalizacionCalculada}");
            $pago->penalizacion = $penalizacionCalculada;
        }

        $detallesPago['descripcion'] .= "Se abonó por penalización estandarizada Q{$montoPenalizacion} (Calculada: Q{$penalizacionCalculada}); ";
        $detallesPago['penalizacion'] += $montoPenalizacion;
        
        $this->log("✅ Penalización procesada: Q{$montoPenalizacion} de Q{$penalizacionPendiente} pendiente");
        
        return $montoDisponible - $montoPenalizacion;
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

        $detallesPago['descripcion'] .= "Se abonó por penalización la cantidad de Q.{$montoPenalizacion}; ";

        $detallesPago['penalizacion'] += $montoPenalizacion;
        return $montoDisponible - $montoPenalizacion;
    }


    /**
     * Procesa la penalización existente para un pago existente
     * @param mixed $pago informacion del pago
     * @param mixed $montoDisponible monto disponible para el pago
     * @param mixed $detallesPago detalles del pago
     * @param mixed $penalizacion información de la penalización
     */
    private function procesarPenalizacionExistente($pago, $montoDisponible, &$detallesPago, $penalizacion)
    {
        $pago->penalizacion = $penalizacion;
        return $this->procesarPenalizacion($pago, $montoDisponible, $detallesPago);
    }

    /**
     * Calcula la penalización para un pago hipotecario en función de la fecha de depósito.
     *
     * @param Pago $pago Pago a evaluar
     * @param string $fechaDeposito Fecha del depósito
     * @return float Penalización calculada
     */
    private function calcularPenalizacion($pago, $fechaDeposito)
    {
        // Ejemplo de lógica: penalización si el depósito es posterior a la fecha de pago
        // Puedes ajustar la lógica según las reglas de negocio
        $penalizacion = 0.0;
        $this->log("Calculando penalización para la fecha: {$pago->fecha}");
        $this->log("Fecha de depósito: {$fechaDeposito}");
        if (!empty($pago->fecha) && !empty($fechaDeposito)) {
            // La fecha de pago es el día 10 del mes de $pago->fecha
            $fechaPago = new \DateTime(date('Y-m-10', strtotime($pago->fecha)));

            $this->log("Fecha de pago establecida: " . $fechaPago->format('Y-m-d'));
            $fechaDepositoObj = new \DateTime($fechaDeposito);
            if ($fechaDepositoObj > $fechaPago) {
                // Penalización: 5% del capital si el pago es tardío
                $penalizacion = round($pago->capital * 0.05, 2);
            }
        }
        return $penalizacion;
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

        $respuesta = $this->bitacoraInteresService->calcularInteresPendiente($pago, $fechaPago);
        $interesPendiente = $respuesta['interes_pendiente'];
        $this->log("Interés pendiente calculado: Q{$interesPendiente}");
        if ($interesPendiente <= 0) {
            $this->log("No hay interés pendiente para procesar");
            return $montoDisponible;
        }
        $this->log("Procesando interés: {$interesPendiente}");
        $montoInteres = min($montoDisponible, $interesPendiente);
        $pago->interes_pagado += $montoInteres;
        $detallesPago['interesGanado'] += $montoInteres;
        $this->log("Se abonó a interés la cantidad de Q.{$montoInteres}");
        $detallesPago['descripcion'] .= "Se abonó a interés la cantidad de Q.{$montoInteres}; ";
        $this->bitacoraInteresService->actualizarInteresPagado($respuesta['id_historico'], $montoInteres);
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
        $this->log("Se abonó a capital la cantidad de Q.{$montoDisponible}");
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

        // Actualizar la fecha final del préstamo basándose en la última cuota activa
        $this->actualizarFechaFinalPrestamo($prestamoHipotecario);

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
            $this->lanzarExcepcionConCodigo("Profundidad de recursión excesiva al eliminar pagos");
        }

        // Verificar que el pago no esté realizado
        if ($pago->realizado) {
            $this->logError("Intento de eliminar pago realizado #{$pago->id}");
            $this->lanzarExcepcionConCodigo("No se puede eliminar un pago ya realizado (#{$pago->id})");
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
            'penalizacion' => $detallesPago['penalizacion'],
            'motivo' => $descripcion,
            'saldo' => $pago->nuevo_saldo > 0 ? $pago->nuevo_saldo : ($pago->saldo + $pago->capital),
            'id_cuenta' => $data['id_cuenta'],
            'existente' => $data['existente'],
            'fecha' => $data['fecha_documento'] ?? now()
        ]);
    }


    /**
     * Verifica si una fecha es válida para iniciar pagos (días 1-5)
     *
     * @param string $fecha Fecha a validar
     * @return bool True si la fecha es válida para pagos
     * @throws \Exception Si la fecha es inválida
     */
    private function esFechaValida($fecha)
    {
        try {
            if (empty($fecha)) {
                $this->lanzarExcepcionConCodigo("La fecha no puede estar vacía para validación");
            }

            $timestamp = strtotime($fecha);
            if ($timestamp === false) {
                $this->lanzarExcepcionConCodigo("Formato de fecha inválido para validación: {$fecha}");
            }

            $dia = (int)date('j', $timestamp);
            $this->log("Validando fecha {$fecha} - Día: {$dia}");

            $esValida = $dia >= 1 && $dia <= 5;
            $this->log($esValida ? "Fecha válida para pagos" : "Fecha NO válida para pagos (día debe estar entre 1-5)");

            return $esValida;
        } catch (\Exception $e) {
            $this->manejarError($e, 'esFechaValida');
            return false; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Calcula la cuota mensual usando la fórmula de amortización francesa
     *
     * @param float $monto Monto del préstamo
     * @param float $interes Tasa de interés anual
     * @param int $plazo Plazo en meses
     * @return float Cuota mensual calculada
     * @throws \Exception Si los parámetros son inválidos
     */
    private function calcularCuota($monto, $interes, $plazo)
    {
        try {
            $this->log("Calculando cuota: Monto=Q{$monto}, Interés={$interes}%, Plazo={$plazo} meses");

            // Validaciones básicas
            if ($monto <= 0) {
                $this->lanzarExcepcionConCodigo("El monto del préstamo debe ser mayor a cero");
            }
            if ($interes < 0) {
                $this->lanzarExcepcionConCodigo("La tasa de interés no puede ser negativa");
            }
            if ($plazo <= 0) {
                $this->lanzarExcepcionConCodigo("El plazo debe ser mayor a cero");
            }

            // Si no hay interés, la cuota es simplemente el monto dividido entre el plazo
            if ($interes == 0) {
                $cuota = round($monto / $plazo, 2);
                $this->log("Cuota calculada sin interés: Q{$cuota}");
                return $cuota;
            }

            $tasaInteresMensual = $this->calcularTaza($interes);
            $this->log("Tasa de interés mensual: {$tasaInteresMensual}");

            // Fórmula de amortización francesa: C = P * (r * (1 + r)^n) / ((1 + r)^n - 1)
            $factorInteres = pow(1 + $tasaInteresMensual, $plazo);
            $cuota = ($monto * $tasaInteresMensual * $factorInteres) / ($factorInteres - 1);

            // Redondear a 2 decimales para evitar problemas de precisión
            $cuota = round($cuota, 2);
            $this->log("Cuota mensual calculada: Q{$cuota}");

            // Validar que la cuota calculada sea razonable
            $totalCuotasEstimado = $cuota * $plazo;
            $this->log("Total estimado de cuotas: Q{$totalCuotasEstimado}");

            return $cuota;
        } catch (\Exception $e) {
            $this->manejarError($e, 'calcularCuota');
            return 0; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Calcula el interés basado en el monto y la tasa
     *
     * @param float $monto Monto sobre el cual calcular interés
     * @param float $tasa Tasa de interés (decimal)
     * @return float Interés calculado
     * @throws \Exception Si los parámetros son inválidos
     */
    private function calcularInteres($monto, $tasa)
    {
        try {
            if ($monto < 0) {
                $this->lanzarExcepcionConCodigo("El monto no puede ser negativo");
            }
            if ($tasa < 0) {
                $this->lanzarExcepcionConCodigo("La tasa de interés no puede ser negativa");
            }

            $interes = $monto * $tasa;
            $interes = round($interes, 2);

            $this->log("Interés calculado: Monto=Q{$monto} × Tasa={$tasa} = Q{$interes}");
            return $interes;
        } catch (\Exception $e) {
            $this->manejarError($e, 'calcularInteres');
            return 0; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Calcula la tasa de interés diario para un período específico
     *
     * @param float $interes Tasa de interés mensual
     * @param string $fecha Fecha de referencia
     * @return float Tasa de interés diario ajustada
     * @throws \Exception Si los parámetros son inválidos
     */
    private function calcularInteresDiario($interes, $fecha)
    {
        try {
            $this->log("Calculando interés diario: Interés={$interes}%, Fecha={$fecha}");

            if ($interes < 0) {
                $this->lanzarExcepcionConCodigo("El interés no puede ser negativo");
            }
            if (empty($fecha)) {
                $this->lanzarExcepcionConCodigo("La fecha es requerida para cálculo de interés diario");
            }

            $diasFaltantes = $this->calcularDiasFaltantes($fecha);
            $diasDelMes = $this->obtenerDiasDelMes($fecha, 0);

            $this->log("Días faltantes: {$diasFaltantes}, Días del mes: {$diasDelMes}");

            if ($diasDelMes <= 0) {
                $this->lanzarExcepcionConCodigo("Los días del mes deben ser mayor a cero");
            }

            // Calcular tasa diaria
            $tasaDiaria = ($interes / 100) / $diasDelMes; // Convertir porcentaje a decimal
            $tasaInteresDiaria = $tasaDiaria * $diasFaltantes;

            $this->log("Tasa de interés diario calculada: {$tasaInteresDiaria}");
            return $tasaInteresDiaria;
        } catch (\Exception $e) {
            $this->manejarError($e, 'calcularInteresDiario');
            return 0; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Calcula los días faltantes hasta el siguiente mes de pago
     *
     * @param string $fecha Fecha de referencia
     * @return int Número de días faltantes
     * @throws \Exception Si la fecha es inválida
     */
    private function calcularDiasFaltantes($fecha)
    {
        try {
            $this->log('Calculando días faltantes desde fecha: ' . $fecha);

            if (empty($fecha)) {
                $this->lanzarExcepcionConCodigo("La fecha no puede estar vacía");
            }

            $fechaActual = new \DateTime($fecha);
            $fechaSiguiente = $this->obtenerFechaSiguienteMes($fecha);
            $this->log('Fecha siguiente de pago: ' . $fechaSiguiente);

            $fechaSiguienteObj = new \DateTime($fechaSiguiente);
            $diferencia = $fechaActual->diff($fechaSiguienteObj);

            $diasFaltantes = (int)$diferencia->format("%a") + 1;
            $this->log("Días faltantes calculados: {$diasFaltantes}");

            return $diasFaltantes;
        } catch (\Exception $e) {
            $this->manejarError($e, 'calcularDiasFaltantes');
            return 0; // Esta línea nunca se ejecutará
        }
    }
    /**
     * Obtiene la fecha del siguiente mes de pago (siempre día 5)
     *
     * @param string $fecha Fecha de referencia
     * @param bool $nuevo Indica si es para generar un nuevo pago
     * @return string Fecha del siguiente mes en formato Y-m-d
     * @throws \Exception Si la fecha es inválida
     */
    private function obtenerFechaSiguienteMes($fecha, $nuevo = false)
    {
        try {
            $this->log('Calculando siguiente mes desde fecha: ' . $fecha);

            if (empty($fecha)) {
                $this->lanzarExcepcionConCodigo("La fecha no puede estar vacía");
            }

            $timestamp = strtotime($fecha);
            if ($timestamp === false) {
                $this->lanzarExcepcionConCodigo("Formato de fecha inválido: {$fecha}");
            }

            $dia = (int)date('j', $timestamp);
            $this->log("Día extraído: {$dia}");

            if ($nuevo || $dia > 5) {
                $fechaSiguiente = date('Y-m-05', strtotime($fecha . ' + 1 month'));
                $this->log("Fecha siguiente mes (nuevo pago): {$fechaSiguiente}");
                return $fechaSiguiente;
            }

            $fechaActual = date('Y-m-05', $timestamp);
            $this->log("Fecha del mes actual (día <= 5): {$fechaActual}");
            return $fechaActual;
        } catch (\Exception $e) {
            $this->manejarError($e, 'obtenerFechaSiguienteMes');
            return date('Y-m-05'); // Esta línea nunca se ejecutará
        }
    }

    /**
     * Obtiene la fecha anterior de pago (día 5 del mes anterior o actual)
     *
     * @param string $fecha Fecha de referencia
     * @return string Fecha anterior en formato Y-m-d
     * @throws \Exception Si la fecha es inválida
     */
    private function obtenerFechaAnterior($fecha)
    {
        try {
            $this->log('Obteniendo fecha anterior desde: ' . $fecha);

            if (empty($fecha)) {
                $this->lanzarExcepcionConCodigo("La fecha no puede estar vacía");
            }

            $timestamp = strtotime($fecha);
            if ($timestamp === false) {
                $this->lanzarExcepcionConCodigo("Formato de fecha inválido: {$fecha}");
            }

            $dia = (int)date('j', $timestamp);
            $this->log("Día de la fecha: {$dia}");

            if ($dia <= 5) {
                $fechaAnterior = date('Y-m-05', strtotime($fecha . ' - 1 month'));
                $this->log("Fecha anterior (día <= 5): {$fechaAnterior}");
                return $fechaAnterior;
            }

            $fechaActual = date('Y-m-05', $timestamp);
            $this->log("Fecha del mes actual (día > 5): {$fechaActual}");
            return $fechaActual;
        } catch (\Exception $e) {
            $this->manejarError($e, 'obtenerFechaAnterior');
            return date('Y-m-05', strtotime('-1 month')); // Esta línea nunca se ejecutará
        }
    }

    /**
     * Obtiene los días acumulados desde la fecha anterior de pago hasta la fecha actual
     *
     * @param string $fecha Fecha actual de referencia
     * @return int Número de días acumulados
     * @throws \Exception Si la fecha es inválida
     */
    private function obtenerDiasAcumulados($fecha)
    {
        try {
            $this->log('Calculando días acumulados para fecha: ' . $fecha);

            if (empty($fecha)) {
                $this->lanzarExcepcionConCodigo("La fecha no puede estar vacía");
            }

            $fechaAnterior = $this->obtenerFechaAnterior($fecha);
            $this->log('Fecha anterior de pago: ' . $fechaAnterior);

            $fechaAnteriorObj = new \DateTime($fechaAnterior);
            $fechaActualObj = new \DateTime($fecha);

            $diferencia = $fechaAnteriorObj->diff($fechaActualObj);
            $diasAcumulados = (int)$diferencia->format("%a");

            $this->log("Días acumulados calculados: {$diasAcumulados}");
            return $diasAcumulados;
        } catch (\Exception $e) {
            $this->manejarError($e, 'obtenerDiasAcumulados');
            return 0; // Esta línea nunca se ejecutará
        }
    }

    /**
     * Método público para actualizar la fecha final de un préstamo
     * Útil para ser llamado desde controladores o otros servicios
     *
     * @param int $prestamoId ID del préstamo hipotecario
     * @return bool True si se actualizó correctamente
     */
    public function sincronizarFechaFinalPrestamo(int $prestamoId): bool
    {
        try {
            $prestamo = Prestamo_Hipotecario::findOrFail($prestamoId);
            $this->actualizarFechaFinalPrestamo($prestamo);
            return true;
        } catch (\Exception $e) {
            $this->manejarError($e, 'sincronizarFechaFinalPrestamo');
            return false;
        }
    }

    /**
     * Valida la integridad de los cálculos de un préstamo
     * Útil para verificar que no hay faltantes por redondeo
     *
     * @param int $prestamoId ID del préstamo hipotecario
     * @return array Resultado de la validación con detalles
     */
    public function validarIntegridadCalculos(int $prestamoId): array
    {
        try {
            $prestamo = Prestamo_Hipotecario::findOrFail($prestamoId);
            $pagos = $this->getPagos($prestamo);

            $totalCapital = $pagos->sum('capital');
            $totalInteres = $pagos->sum('interes');
            $totalCuotas = $totalCapital + $totalInteres;

            $faltanteCapital = $prestamo->monto - $totalCapital;
            $ultimoPago = $pagos->last();

            $resultado = [
                'prestamo_id' => $prestamoId,
                'monto_original' => $prestamo->monto,
                'total_capital_calculado' => round($totalCapital, 2),
                'total_interes_calculado' => round($totalInteres, 2),
                'total_cuotas' => round($totalCuotas, 2),
                'faltante_capital' => round($faltanteCapital, 2),
                'saldo_final' => $ultimoPago ? $ultimoPago->saldo : 0,
                'numero_cuotas' => $pagos->count(),
                'integridad_ok' => abs($faltanteCapital) < 0.01 && ($ultimoPago ? $ultimoPago->saldo < 0.01 : true)
            ];

            $this->log("Validación de integridad completada para préstamo #{$prestamoId}: " .
                ($resultado['integridad_ok'] ? 'CORRECTA' : 'CON PROBLEMAS'));

            return $resultado;
        } catch (\Exception $e) {
            $this->manejarError($e, 'validarIntegridadCalculos');
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Verifica y ajusta las amortizaciones para garantizar que sumen exactamente el monto del préstamo
     * Corrige errores de redondeo acumulativo ajustando la última cuota
     *
     * @param Prestamo_Hipotecario $prestamo Préstamo a verificar
     * @return bool True si se realizaron ajustes
     */
    public function verificarYAjustarAmortizaciones(Prestamo_Hipotecario $prestamo): bool
    {
        try {
            $this->log("🔍 Verificación final de amortizaciones para préstamo #{$prestamo->id}");

            // Obtener todos los pagos del préstamo ordenados por número
            $pagos = Pago::where('id_prestamo', $prestamo->id)
                ->orderBy('numero_pago_prestamo')
                ->get();

            if ($pagos->isEmpty()) {
                $this->log("❌ No hay pagos para verificar");
                return false;
            }

            // Calcular suma total de amortizaciones (capital)
            $sumaAmortizaciones = $pagos->sum('capital');
            $diferencia = $prestamo->monto - $sumaAmortizaciones;
            $umbral = 0.005; // Umbral más estricto para detección de diferencias

            $this->log("💰 Monto original: Q{$prestamo->monto}");
            $this->log("📊 Suma de amortizaciones: Q{$sumaAmortizaciones}");
            $this->log("⚖️ Diferencia: Q{$diferencia}");

            // Si la diferencia es menor al umbral, consideramos que está correcto
            if (abs($diferencia) < $umbral) {
                $this->log("✅ Las amortizaciones están correctas (diferencia < Q{$umbral})");
                return false;
            }

            // Hay diferencia significativa, necesitamos corregir
            $this->log("⚠️ AJUSTANDO ÚLTIMA CUOTA - Diferencia detectada: Q{$diferencia}");

            // Obtener la última cuota
            $ultimaCuota = $pagos->last();
            if (!$ultimaCuota) {
                $this->log("❌ No se encontró la última cuota");
                return false;
            }

            $capitalAnterior = $ultimaCuota->capital;

            // Ajustar el capital de la última cuota
            $ultimaCuota->capital = round($ultimaCuota->capital + $diferencia, 2);

            // Asegurar que el saldo de la última cuota sea 0
            $ultimaCuota->saldo = 0;

            // Guardar cambios
            $ultimaCuota->save();

            $this->log("🔧 Capital de última cuota ajustado: Q{$capitalAnterior} → Q{$ultimaCuota->capital}");

            // Verificar que la corrección funcionó
            $ultimaCuota->refresh();
            $nuevaSuma = Pago::where('id_prestamo', $prestamo->id)->sum('capital');
            $nuevaDiferencia = $prestamo->monto - $nuevaSuma;

            $this->log("🔍 Verificación post-ajuste:");
            $this->log("📊 Nueva suma de amortizaciones: Q{$nuevaSuma}");
            $this->log("⚖️ Nueva diferencia: Q{$nuevaDiferencia}");

            if (abs($nuevaDiferencia) < 0.01) {
                $this->log("✅ Suma de amortizaciones corregida exitosamente");
                return true;
            } else {
                $this->log("❌ El ajuste no corrigió completamente la diferencia");
                return false;
            }
        } catch (\Exception $e) {
            $this->logError("Error en verificarYAjustarAmortizaciones: " . $e->getMessage());
            $this->manejarError($e, 'verificarYAjustarAmortizaciones');
            return false;
        }
    }
}
