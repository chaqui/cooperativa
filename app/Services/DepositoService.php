<?php

namespace App\Services;

use App\Constants\EstadoInversion;
use App\Models\Deposito;
use App\Models\Pago;
use App\Traits\Loggable;
use App\Constants\TipoImpuesto;
use Illuminate\Support\Facades\DB;

class DepositoService
{

    use Loggable;

    private $pdfService;

    private ImpuestoTransaccionService $impuestoTransaccionService;

    public function __construct(PdfService $pdfService, ImpuestoTransaccionService $impuestoTransaccionService)
    {
        $this->pdfService = $pdfService;
        $this->impuestoTransaccionService = $impuestoTransaccionService;
    }
    /**
     * Crea un nuevo registro de depósito en el sistema - No es llamado desde el controlador
     *
     * @param array $datos Datos del depósito con las siguientes claves:
     *        - monto: (requerido) Cantidad monetaria del depósito
     *        - tipo_documento: (opcional) Tipo de documento que respalda el depósito
     *        - numero_documento: (opcional) Número del documento
     *        - id_inversion: (opcional) ID de la inversión asociada
     *        - id_pago: (opcional) ID del pago asociado
     *        - tipo_cuenta_interna_id: (opcional) ID del tipo de cuenta interna
     *        - imagen: (opcional) URL o ruta de la imagen del comprobante
     * @return Deposito Instancia del depósito creado
     * @throws \InvalidArgumentException Si faltan datos requeridos o son inválidos
     * @throws \Exception Si ocurre un error durante el proceso de creación
     */
    public function crearDeposito(array $datos)
    {
        // Validar los datos requeridos
        $this->validarDatosDeposito($datos);
        return $this->procesarCreacionDeposito($datos);
    }

    private function procesarCreacionDeposito($datos, $procesarInmediatamente = false)
    {
        $this->log("Creando depósito con datos: " . json_encode($datos));
        $deposito = Deposito::crear($datos);

        // Guardar el depósito
        $deposito->save();

        // Si está asociado a un pago, procesarlo automáticamente
        if ($procesarInmediatamente || $deposito->id_pago) {
            $this->depositar($deposito->id, $datos);
        }
        if (!$deposito instanceof Deposito) {
            throw new \Exception('Unexpected return type: Expected instance of App\Models\Deposito.');
        }
        return $deposito;
    }

    /**
     * Crea un nuevo depósito interno y lo marca como realizado automáticamente - Llamado desde el controlador
     *
     * @param array $datos Datos del depósito interno con las siguientes claves:
     *        - monto: (requerido) Cantidad monetaria del depósito
     *        - motivo: (requerido) Descripción/razón del depósito
     *        - tipo_cuenta_interna_id: (requerido) ID del tipo de cuenta interna
     *        - tipo_documento: (requerido) Tipo de documento que respalda el depósito
     *        - numero_documento: (requerido) Número del documento
     *        - imagen: (opcional) URL o ruta de la imagen del comprobante
     * @return Deposito Instancia del depósito creado
     * @throws \InvalidArgumentException Si faltan datos requeridos o son inválidos
     * @throws \Exception Si ocurre un error durante el proceso
     */
    public function crearDepositoInterno($datos)
    {
        DB::beginTransaction();
        try {
            $deposito = $this->procesarCreacionDeposito($datos, true);
            DB::commit();
            return $deposito;
        } catch (\Exception $e) {
            DB::rollBack();
            // Registrar error detallado
            $this->logError(
                'Error al procesar el depósito' .
                    ' Mensaje: ' . $e->getMessage() .
                    ' Datos: ' . json_encode($datos)
            );
            throw $e;
        }
    }


    public function getDeposito($id)
    {
        return Deposito::findOrFail($id);
    }
    public function getDepositos()
    {
        return Deposito::all();
    }

    /**
     * Marca un depósito como realizado y actualiza su información
     *
     * @param int $id ID del depósito a procesar
     * @param array $data Datos para actualizar el depósito con las siguientes claves:
     *        - tipo_documento: (requerido) Tipo de documento que respalda el depósito
     *        - numero_documento: (requerido) Número del documento
     *        - imagen: (opcional) URL o ruta de la imagen del comprobante
     * @return Deposito Instancia del depósito actualizado
     * @throws \InvalidArgumentException Si faltan datos requeridos
     * @throws \Exception Si el depósito ya ha sido realizado o ocurre otro error
     */
    public function depositar($id, $data)
    {
        DB::beginTransaction();
        try {
            $this->log("Procesando depósito con ID: {$id} y datos: " . json_encode($data));
            // Obtener el depósito
            $deposito = $this->getDeposito($id);

            // Verificar si ya está realizado
            if ($deposito->realizado) {
                throw new \Exception('El depósito ya ha sido realizado.');
            }

            // Actualizar datos del depósito
            $deposito->depositar($data);

            // Guardar cambios
            $deposito->save();

            // Registrar la transacción en la cuenta interna
            $cuentaInternaService = app(CuentaInternaService::class);

            if (isset($data['interes']) && $data['interes'] > 0.001) {
                $this->generarImpuestos($deposito->pago, $data['interes'], $deposito->tipo_cuenta_interna_id);
            }

            $cuentaInternaService->createCuenta($deposito->dataCuenta($data['interes'] ?? 0));

            // Si está asociado a una inversión, actualizar su estado
            if ($deposito->id_inversion) {
                $this->actualizarEstadoInversion($deposito, $data['descripcion'] ?? null);
            }

            DB::commit();
            return $deposito;
        } catch (\Exception $e) {
            DB::rollBack();

            // Registrar error detallado
            $this->logError(
                'Error al procesar el depósito' .
                    ' ID: ' . $id .
                    ' Mensaje: ' . $e->getMessage() .
                    ' Datos: ' . json_encode($data)
            );
            // Lanzar excepción para que el controlador maneje el error
            throw $e;
        }
    }

    /**
     * Metodo privado para actualizar el estado de una inversión asociada a un depósito
     * @param mixed $deposito Información del depósito
     * @param mixed $descripcion Descripción adicional para el estado
     * @throws \Exception Si ocurre un error al actualizar el estado de la inversión
     * @return void
     */
    private function actualizarEstadoInversion($deposito, $descripcion = null)
    {
        try {
            $inversionService = app(InversionService::class);
            $estadoData = ['estado' => EstadoInversion::$DEPOSITADO];

            // Conservar datos relevantes del depósito para el historial
            if (isset($descripcion)) {
                $estadoData['descripcion'] = $descripcion;
            }
            $estadoData['numero_documento'] = $deposito->numero_documento;
            $estadoData['tipo_documento'] = $deposito->tipo_documento;
            $inversionService->cambiarEstado($deposito->id_inversion, $estadoData);
        } catch (\Exception $e) {
            DB::rollBack();
            throw new \Exception('Error al actualizar el estado de la inversión: ' . $e->getMessage());
        }
    }


    /**
     * Genera y registra los impuestos asociados a un pago de intereses de una cuota hipotecaria
     *
     * @param Pago $pago Pago asociado al impuesto
     * @param float $montoInteres Monto de los intereses sobre los cuales se calculará el impuesto
     * @param int $idCuenta ID de la cuenta interna donde se registrará el impuesto
     * @return void
     * @throws \Exception Si ocurre un error durante el proceso
     */
    private function generarImpuestos(Pago $pago, float $montoInteres, int $idCuenta): void
    {
        try {
            $this->log("Generando impuestos para el pago de intereses del préstamo #{$pago->id_prestamo}");
            // Validar que el monto de intereses sea mayor a cero
            if ($montoInteres <= 0) {
                $this->log("No se generaron impuestos porque el monto de intereses es cero o negativo.");
                return;
            }

            // Calcular la fecha actual
            $fechaPago = now();

            // Generar la descripción del impuesto
            $descripcion = sprintf(
                'Impuesto por pago de intereses de la cuota hipotecaria del préstamo #%d (código: %s) con fecha %s',
                $pago->id_prestamo,
                $pago->prestamo->codigo,
                $fechaPago->format('Y-m-d')
            );

            // Crear la transacción de impuesto
            $impuesto = $this->impuestoTransaccionService->crearTransaccion([
                'tipo_impuesto' => TipoImpuesto::$IVA,
                'monto_transaccion' => $montoInteres,
                'fecha_transaccion' => $fechaPago->format('Y-m-d'),
                'descripcion' => $descripcion,
                'id_cuenta' => $idCuenta,
            ]);

            // Registrar en los logs
            $this->log(sprintf(
                'Impuesto generado con éxito: ID %d, Monto: Q%.2f, y bloqueado el monto en Cuenta Interna: %d',
                $impuesto->id,
                $impuesto->monto_impuesto,
                $idCuenta
            ));
        } catch (\Exception $e) {
            $this->logError('Error al generar impuestos: ' . $e->getMessage());
            throw new \Exception('Error al generar impuestos: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Genera un PDF del depósito
     *
     * @param int $id ID del depósito a generar el PDF
     * @return string Ruta del archivo PDF generado
     */
    public function generarPdf($id)
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException("El ID del depósito debe ser un número entero positivo");
        }

        $this->log("Iniciando generación de PDF para depósito #{$id}");

        $deposito = $this->getDeposito($id);
        $html = view('pdf.deposito', ['deposito' => $deposito])->render();
        return $this->pdfService->generatePdf($html);
    }

    /**
     * Valida los datos requeridos para crear un depósito
     *
     * @param array $datos Datos a validar
     * @throws \InvalidArgumentException Si los datos son inválidos
     */
    private function validarDatosDeposito(array $datos)
    {
        // Validar que exista el monto y sea válido
        if (!isset($datos['monto'])) {
            throw new \InvalidArgumentException("El monto es requerido para crear un depósito");
        }

        if (!is_numeric($datos['monto']) || $datos['monto'] <= 0) {
            throw new \InvalidArgumentException("El monto debe ser un valor numérico mayor a cero");
        }

        // Validar que exista al menos una relación (inversión o pago)
        if (!isset($datos['id_inversion']) && !isset($datos['id_pago'])) {
            throw new \InvalidArgumentException("El depósito debe estar asociado a una inversión o un pago");
        }
        if (!isset($datos['motivo']) && empty($datos['motivo'])) {
            throw new \InvalidArgumentException("El motivo es requerido");
        }
    }
}
