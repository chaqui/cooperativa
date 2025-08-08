<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\Models\Retiro;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Traits\ErrorHandler;

class RetiroService
{
 use ErrorHandler;
    use Loggable;
    private CuentaInternaService $cuentaInternaService;

    private TipoCuentaInternaService $tipoCuentaInternaService;

    private PdfService $pdfService;

    public function __construct(
        CuentaInternaService $cuentaInternaService,
        TipoCuentaInternaService $tipoCuentaInternaService,
        PdfService $pdfService
    ) {
        $this->cuentaInternaService = $cuentaInternaService;
        $this->tipoCuentaInternaService = $tipoCuentaInternaService;
        $this->pdfService = $pdfService;
    }
    // Crear un nuevo retiro
    public function crearRetiro(array $data)
    {
        $this->log("Iniciando creación de retiro");

        try {
            // Validar datos requeridos
            $this->validarDatosRetiro($data);

            // Iniciar transacción
            DB::beginTransaction();
            $retiro = $this->procesarCreacionRetiro($data);

            DB::commit();
            return $retiro;
        } catch (\InvalidArgumentException $e) {

            // Para excepciones de validación, no necesitamos rollback pues no se inició transacción
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        } catch (\Exception $e) {
            // Para cualquier otra excepción, hacer rollback si hay transacción activa
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->logError("Error inesperado al crear retiro: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString() . " | Data: " . json_encode(array_diff_key($data, ['imagen' => ''])));

            throw new \Exception("No se pudo crear el retiro: " . $e->getMessage(), 0, $e);
        }
    }

    public function crearRetiroInterno($data)
    {
        try {


            // Iniciar transacción
            DB::beginTransaction();
            $retiro = $this->procesarCreacionRetiro($data, true);

            DB::commit();
            return $retiro;
        } catch (\InvalidArgumentException $e) {
            // Para excepciones de validación, no necesitamos rollback pues no se inició transacción
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            throw $e;
        } catch (\Exception $e) {
            // Para cualquier otra excepción, hacer rollback si hay transacción activa
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            $this->logError("Error inesperado al crear retiro: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString() . " | Data: " . json_encode(array_diff_key($data, ['imagen' => ''])));

            throw new \Exception("No se pudo crear el retiro: " . $e->getMessage(), 0, $e);
        }
    }

    // Obtener todos los retiros
    public function obtenerRetiros()
    {
        return Retiro::all();
    }

    // Realizar un retiro con lógica de validación y transacción
    public function realizarRetiro(int $id, array $data)
    {
        $this->log("Iniciando proceso de retiro #$id");

        DB::beginTransaction();
        try {
            $retiro = Retiro::findOrFail($id);

            // Verificar si ya está realizado
            if ($retiro->realizado) {
                $this->logError("Intento de realizar un retiro ya procesado #$id");
                $this->lanzarExcepcionConCodigo("El retiro ya ha sido realizado.");
            }

            // Actualizar datos del retiro
            $retiro->tipo_documento = $data['tipo_documento'];
            $retiro->numero_documento = $data['numero_documento'];
            $retiro->imagen = $data['imagen'] ?? null;
            $retiro->realizado = true;
            $retiro->save();

            $this->log("Retiro #$id marcado como realizado");

            $descripcion = "Retiro realizado: " . ($retiro->motivo ?? 'No especificado') .
                " | Documento: " . ($data['tipo_documento'] ?? 'No especificado') .
                " | Número: " . ($data['numero_documento'] ?? 'No especificado');

            // Registrar la transacción en la cuenta interna
            $this->cuentaInternaService->createCuenta([
                'ingreso' => 0,
                'egreso' => $retiro->monto,
                'interes' => 0,
                'capital' => $retiro->monto,
                'descripcion' => $descripcion,
                'tipo_cuenta_interna_id' => $retiro->tipo_cuenta_interna_id,
            ]);

            $this->log("Transacción registrada en cuenta interna para retiro #$id");

            // Si el retiro está relacionado con un préstamo hipotecario, procesar el retiro del prestamo
            if ($retiro->id_prestamo) {
                $this->retirarPrestamo($retiro, $data);
            }

            // Si el retiro está relacionado con una cuota de inversión, procesar el retiro del pago de inversión
            if ($retiro->id_pago_inversions) {
                $this->retirarCuotaInversion($retiro);
            }

            DB::commit();
            $this->log("Retiro #$id completado exitosamente");

            return $retiro;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError("Error al realizar retiro #$id: " . $e->getMessage());
            throw $e;
        }
    }

    private function retirarPrestamo($retiro, $data)
    {
        $this->log("Actualizando estado de préstamo #{$retiro->id_prestamo} a DESEMBOLSADO");

        $prestamoService = app(EstadosPrestamoService::class);
        $estadoData = array_merge($data, ['estado' => EstadoPrestamo::$DESEMBOLZADO]);

        try {
            $prestamoService->cambiarEstado($retiro->id_prestamo, $estadoData);
            $this->log("Estado de préstamo actualizado correctamente");
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * Procesa el retiro de una cuota de inversión
     *
     * @param Retiro $retiro Retiro asociado a la cuota de inversión
     * @return bool True si la operación se realizó correctamente
     * @throws \Exception Si ocurre un error durante el proceso
     */
    private function retirarCuotaInversion(Retiro $retiro): bool
    {


        $idPago = $retiro->id_pago_inversions;
        $idCuenta = $retiro->tipo_cuenta_interna_id;
        $monto = $retiro->monto;

        // No iniciar una nueva transacción, ya que este método es llamado dentro de una transacción existente
        try {
            $this->log("Procesando retiro de cuota de inversión #{$idPago} por monto Q{$monto}");

            // Obtener el servicio de pagos de inversión
            $pagoService = app(PagoInversionService::class);

            // Marcar el pago como realizado
            $pagoService->pagar($idPago);

            // Desbloquear el monto en la cuenta interna
            $this->tipoCuentaInternaService->desbloquearMonto($idCuenta, $monto);

            $retiro->save();

            $this->log("Retiro de cuota de inversión #{$idPago} completado exitosamente");

            return true;
        } catch (\Exception $e) {
            $this->logError("Error al procesar retiro de cuota de inversión #{$idPago}: " . $e->getMessage());

            throw new \Exception(
                "No se pudo procesar el retiro de la cuota de inversión: " . $e->getMessage(),
                0,
                $e
            );
        }
    }

    public function getRetiro($id)
    {
        // Validar que el ID sea válido
        if (empty($id) || !is_numeric($id) || $id <= 0) {
            $this->logError("ID de retiro inválido: {$id}");
            $this->lanzarExcepcionConCodigo("El ID del retiro debe ser un valor numérico positivo");
        }
        try {
            $this->log("Buscando retiro con ID: {$id}");
            $retiro = Retiro::findOrFail($id);

            $this->log("Retiro encontrado: #{$retiro->id} por monto Q{$retiro->monto}");
            return $retiro;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logError("Retiro no encontrado con ID: {$id}");
            throw $e; // Re-lanzar la excepción para mantener el comportamiento esperado
        } catch (\Exception $e) {
            $this->logError("Error al obtener retiro #{$id}: " . $e->getMessage());
            throw new \Exception("Error al obtener el retiro: " . $e->getMessage(), 0, $e);
        }
    }

    public function getPDF($id)
    {
        $retiro = $this->getRetiro($id);
        $this->log("Generando PDF para el retiro #{$retiro->id}");
        $this->log("Datos del retiro: " . json_encode($retiro));
        $html = view('pdf.recibo', ['retiro' => $retiro])->render();
        return $this->pdfService->generatePdf($html);
    }

    private function procesarCreacionRetiro($data, $procesar = false)
    {
        // Validar saldo disponible
        $cuenta = $this->tipoCuentaInternaService->getById($data['id_cuenta']);
        $saldo = $cuenta->saldo();

        $saldoSalida = $data['monto_total'] ?? $data['monto'];
        if ($saldo < $saldoSalida) {
            $this->logError("Saldo insuficiente para retiro: " . json_encode([
                'monto_solicitado' => $data['monto'],
                'saldo_disponible' => $saldo,
                'tipo_cuenta_interna_id' => $data['id_cuenta']
            ]));

             $this->lanzarExcepcionConCodigo("El monto a retirar (Q.{$data['monto']}) excede el saldo disponible (Q.{$saldo}).");
        }

        // Preparar datos para el retiro
        $retiroData = [
            'monto' => $data['monto'],
            'tipo_documento' => null,
            'numero_documento' =>  null,
            'motivo' => $data['motivo'],
            'id_prestamo' => $data['id_prestamo'] ?? null,
            'tipo_cuenta_interna_id' => $data['id_cuenta'],
            'realizado' => false, // Por defecto, el retiro se crea como no realizado
        ];

        // Crear el retiro
        $retiro = Retiro::create($retiroData);
        if ($procesar) {
            $this->realizarRetiro($retiro->id, $data);
            $retiro->save();
        }
        $this->log("Retiro creado exitosamente:  id " . $retiro->id . " monto " . $retiro->monto . " tipo_cuenta_interna_id " . $retiro->tipo_cuenta_interna_id);

        return $retiro;
    }

    /**
     * Valida los datos requeridos para crear un retiro
     *
     * @param array $data Datos a validar
     * @throws \InvalidArgumentException Si faltan datos requeridos o son inválidos
     */
    private function validarDatosRetiro(array $data)
    {
        // Validar monto
        if (!isset($data['monto'])) {
            $this->lanzarExcepcionConCodigo("El monto es requerido para crear un retiro");
        }

        if (!is_numeric($data['monto']) || $data['monto'] <= 0) {
            $this->lanzarExcepcionConCodigo("El monto debe ser un valor numérico mayor a cero");
        }

        // Validar tipo de cuenta interna
        if (!isset($data['id_cuenta'])) {
            $this->lanzarExcepcionConCodigo("El tipo de cuenta interna es requerido");
        }

        if ($data['id_prestamo'] != null && !isset($data['motivo']) && empty($data['motivo'])) {
            $this->lanzarExcepcionConCodigo("El motivo es requerido para crear un retiro");
        }

        try {
            $cuenta = $this->tipoCuentaInternaService->getById($data['id_cuenta']);

            if (!$cuenta) {
                $this->lanzarExcepcionConCodigo("El tipo de cuenta interna especificado no existe");
            }
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException("Error al verificar el tipo de cuenta: " . $e->getMessage());
        }
    }
}
