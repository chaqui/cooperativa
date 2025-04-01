<?php

namespace App\Services;

use App\Constants\EstadoInversion;
use App\Models\Deposito;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;

class DepositoService
{

    use Loggable;

    private $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
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
        $deposito = new Deposito();
        $deposito->tipo_documento = null;
        $deposito->numero_documento = null;
        $deposito->monto = $datos['monto'];
        $deposito->id_inversion = $datos['id_inversion'] ?? null;
        $deposito->id_pago = $datos['id_pago'] ?? null;
        $deposito->realizado = false;
        $deposito->imagen = $datos['imagen'] ?? null;
        $deposito->tipo_cuenta_interna_id = null;
        $deposito->motivo = $datos['motivo'];

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
        $this->log('Interese depositar' . $data['interes']);
        DB::beginTransaction();
        try {
            // Obtener el depósito
            $deposito = $this->getDeposito($id);

            // Verificar si ya está realizado
            if ($deposito->realizado) {
                throw new \Exception('El depósito ya ha sido realizado.');
            }

            // Actualizar datos del depósito
            $deposito->realizado = true;
            $deposito->tipo_documento = $data['tipo_documento'];
            $deposito->numero_documento = $data['numero_documento'];
            $deposito->tipo_cuenta_interna_id = $data['id_cuenta'];
            $deposito->imagen = $data['imagen'] ?? $deposito->imagen;

            // Guardar cambios
            $deposito->save();

            // Registrar la transacción en la cuenta interna
            $cuentaInternaService = app(CuentaInternaService::class);
            $descripcion = "Depósito realizado: " . ($deposito->motivo ?? 'No especificado') .
                " | Documento: " . ($data['tipo_documento'] ?? 'No especificado') .
                " | Número: " . ($data['numero_documento'] ?? 'No especificado');
            $cuentaInternaService->createCuenta([
                'ingreso' => $deposito->monto,
                'egreso' => 0,
                'capital' => $deposito->monto - ($data['interes'] ?? 0),
                'interes' => $data['interes'] ?? 0,
                'descripcion' => $descripcion,
                'tipo_cuenta_interna_id' => $deposito->tipo_cuenta_interna_id,
            ]);

            // Si está asociado a una inversión, actualizar su estado
            if ($deposito->id_inversion) {
                try {
                    $inversionService = app(InversionService::class);
                    $estadoData = ['estado' => EstadoInversion::$DEPOSITADO];

                    // Conservar datos relevantes del depósito para el historial
                    if (isset($data['descripcion'])) {
                        $estadoData['descripcion'] = $data['descripcion'];
                    }

                    $inversionService->cambiarEstado($deposito->id_inversion, $estadoData);
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw new \Exception('Error al actualizar el estado de la inversión: ' . $e->getMessage());
                }
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
