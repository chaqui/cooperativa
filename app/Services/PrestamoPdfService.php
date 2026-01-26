<?php

namespace App\Services;


use App\Models\Prestamo_Hipotecario;
use App\EstadosPrestamo\ControladorEstado;
use App\Constants\Orientacion;
use App\Traits\ErrorHandler;
use App\Traits\Loggable;

class PrestamoPdfService extends PrestamoService
{
    use ErrorHandler;
    use Loggable;

    private $pdfService;

    private $bitacoraInteresService;

    public function __construct(
        PdfService $pdfService,
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService,
        PrestamoExistenService $prestamoExistenteService,
        PrestamoArchivoService $prestamoArchivoService,
        PrestamoRemplazadoService $prestamoRemplazadoService,
        BitacoraInteresService $bitacoraInteresService
    ) {
        parent::__construct($controladorEstado, $clientService, $propiedadService, $catalogoService, $userService, $cuotaHipotecaService, $prestamoExistenteService, $prestamoArchivoService, $prestamoRemplazadoService);
        $this->pdfService = $pdfService;
        $this->bitacoraInteresService = $bitacoraInteresService;
    }

    /**
     * Genera el PDF del estado de cuenta de un préstamo
     *
     * @param int $id ID del préstamo
     * @param bool $inicial Indica si se debe generar el estado inicial o actual
     * @param string $orientation Orientación del PDF ('portrait' o 'landscape')
     * @return mixed PDF generado
     * @throws \Exception Si ocurre un error durante el proceso
     */
    public function generarEstadoCuentaPdf($id, $inicial = false, $orientation = Orientacion::LANDSCAPE)
    {
        try {
            $orientation = $this->validateOrientation($orientation);
            $this->log("Generando PDF del estado de cuenta para el préstamo: {$id} en orientación: {$orientation}");
            // Obtener el préstamo y sus datos relacionados
            $prestamo = $this->get($id);
            $this->enriquecerDatosPrestamo($prestamo);

            // Obtener los pagos del préstamo
            $pagos = $prestamo->pagos;

            // Determinar la plantilla a usar
            $plantilla = $inicial ? 'pdf.estadoCuenta' : 'pdf.estadoCuentaActual';
            $interes_pendiente = 0;
            if ($inicial) {
                $interes_pendiente = $prestamo->saldoPendienteIntereses();
            } else {
                $cuotaActiva = $prestamo->cuotaActiva();
                if (!$cuotaActiva) {
                    $interes_pendiente = 0;
                } else {
                    $resultado = $this->bitacoraInteresService->calcularInteresPendiente($cuotaActiva, date('Y-m-d'));
                    $interes_pendiente = $resultado['interes_pendiente'] ?? 0;
                }
            }

            // Renderizar la vista HTML
            $html = view($plantilla, [
                'prestamo' => $prestamo,
                'pagos' => $pagos,
                'orientation' => $orientation,
                'interes_pendiente' => $interes_pendiente,
            ])->render();

            // Generar el PDF
            $pdf = $this->pdfService->generatePdf($html, $orientation);

            $this->log("PDF del estado de cuenta generado con éxito para el préstamo: {$prestamo->codigo}");
            return $pdf;
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * Enriquecer los datos del préstamo con información adicional
     *
     * @param Prestamo_Hipotecario $prestamo Instancia del préstamo
     * @return void
     */
    private function enriquecerDatosPrestamo(Prestamo_Hipotecario $prestamo): void
    {
        $prestamo->totalPagado = $prestamo->totalPagado();
        $prestamo->saldoPendiente = $prestamo->saldoPendiente();
        $prestamo->interesPagado = $prestamo->interesesPagados();
        $prestamo->capitalPagado = $prestamo->capitalPagado();
        $prestamo->nombreCliente = $prestamo->cliente->getFullNameAttribute();
        $prestamo->codigoCliente = $prestamo->cliente->codigo;
    }


    public function generatePdf($id, $orientation = Orientacion::PORTRAIT)
    {
        try {
            $orientation = $this->validateOrientation($orientation);
            $this->log('Generando PDF del prestamo con id: ' . $id . ' en orientación: ' . $orientation);

            $prestamo = $this->get($id);
            $prestamo = $this->getDataForPDF($prestamo);
            $prestamo->cliente = $this->clientService->getDataForPDF($prestamo->dpi_cliente);
            $prestamo->propiedad = $this->propiedadService->getDataPDF($prestamo->propiedad);

            if ($prestamo->fiador_dpi != null) {
                $prestamo->fiador = $this->clientService->getDataForPDF($prestamo->fiador_dpi);
            }

            $html = view('pdf.prestamo', data: compact('prestamo'))->render();
            $pdf = $this->pdfService->generatePdf($html, $orientation);

            $this->log("PDF del préstamo generado con éxito para el préstamo: {$prestamo->codigo}");
            return $pdf;
        } catch (\Exception $e) {
            $this->manejarError($e, 'generatePdf');
        }
    }

    private function getDataForPDF($prestamo)
    {
        try {
            $prestamo->nombreDestino = $this->catalogoService->getCatalogo($prestamo->destino)['value'] ?? 'No especificado';
            $prestamo->nombreFrecuenciaPago = $this->catalogoService->getCatalogo($prestamo->frecuencia_pago)['value'] ?? 'No especificado';
            return $prestamo;
        } catch (\Exception $e) {
            $this->log("Error al obtener datos del catálogo: " . $e->getMessage());
            // Asignar valores por defecto en caso de error
            $prestamo->nombreDestino = 'No especificado';
            $prestamo->nombreFrecuenciaPago = 'No especificado';
            return $prestamo;
        }
    }

    /**
     * Valida y normaliza la orientación del PDF
     *
     * @param string $orientation Orientación solicitada
     * @return string Orientación válida
     * @throws \Exception Si la orientación es null o inválida de manera crítica
     */
    private function validateOrientation($orientation)
    {
        if ($orientation === null) {
            $this->lanzarExcepcionConCodigo("La orientación no puede ser null");
        }

        $orientation = strtolower($orientation);

        if (!Orientacion::isValid($orientation)) {
            $this->log("Orientación '{$orientation}' no válida, usando '" . Orientacion::PORTRAIT . "' por defecto");
            return Orientacion::PORTRAIT;
        }

        return $orientation;
    }

    /**
     * Genera PDF en orientación vertical (portrait)
     *
     * @param int $id ID del préstamo
     * @return mixed PDF generado
     */
    public function generatePdfPortrait($id)
    {
        return $this->generatePdf($id, Orientacion::PORTRAIT);
    }

    /**
     * Genera PDF en orientación horizontal (landscape)
     *
     * @param int $id ID del préstamo
     * @return mixed PDF generado
     */
    public function generatePdfLandscape($id)
    {
        return $this->generatePdf($id, Orientacion::LANDSCAPE);
    }

    /**
     * Genera PDF del estado de cuenta en orientación vertical
     *
     * @param int $id ID del préstamo
     * @param bool $inicial Indica si se debe generar el estado inicial o actual
     * @return mixed PDF generado
     */
    public function generarEstadoCuentaPdfPortrait($id, $inicial = false)
    {
        return $this->generarEstadoCuentaPdf($id, $inicial, Orientacion::PORTRAIT);
    }

    /**
     * Genera PDF del estado de cuenta en orientación horizontal
     *
     * @param int $id ID del préstamo
     * @param bool $inicial Indica si se debe generar el estado inicial o actual
     * @return mixed PDF generado
     */
    public function generarEstadoCuentaPdfLandscape($id, $inicial = false)
    {
        return $this->generarEstadoCuentaPdf($id, $inicial, Orientacion::LANDSCAPE);
    }


    public function generarPdfDepositos($id)
    {
        $prestamo = $this->get($id);

        $depositos = $prestamo->depositos();

        $cuotaActiva = $prestamo->cuotaActiva();
        if (!$cuotaActiva) {
            $interes_pendiente = 0;
        } else {
            $interes_pendiente = $this->bitacoraInteresService->calcularInteresPendiente($prestamo->cuotaActiva(), date('Y-m-d'))['interes_pendiente'] ?? 0;
        }
        $this->enriquecerDatosPrestamo($prestamo);

        $this->log("Interés pendiente calculado: " . ($interes_pendiente ?? 0));
        // Renderizar la vista HTML
        $html = view('pdf.depositos', [
            'prestamo' => $prestamo,
            'depositos' => $depositos,
            'interes_pendiente' => $interes_pendiente,
        ])->render();
        $pdf = $this->pdfService->generatePdf($html);

        $this->log("PDF de depósitos generado con éxito para el préstamo: {$prestamo->codigo}");
        return $pdf;
    }
}
