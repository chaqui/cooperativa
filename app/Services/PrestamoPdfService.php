<?

namespace App\Services;


use App\Models\Prestamo_Hipotecario;
use App\EstadosPrestamo\ControladorEstado;
use App\Constants\Orientacion;

class PrestamoPdfService extends PrestamoService
{

    private $pdfService;


    public function __construct(
        PdfService $pdfService,
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService,
        PrestamoExistenService $prestamoExistenteService
    ) {
        parent::__construct($controladorEstado, $clientService, $propiedadService, $catalogoService, $userService, $cuotaHipotecaService, $prestamoExistenteService);
        $this->pdfService = $pdfService;
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

            // Renderizar la vista HTML
            $html = view($plantilla, [
                'prestamo' => $prestamo,
                'pagos' => $pagos,
                'orientation' => $orientation,
            ])->render();

            // Generar el PDF
            $pdf = $this->pdfService->generatePdf($html, $orientation);

            $this->log("PDF del estado de cuenta generado con éxito para el préstamo: {$prestamo->codigo}");
            return $pdf;
        } catch (\Exception $e) {
            $this->logError("Error al generar el PDF del estado de cuenta: " . $e->getMessage());
            throw new \Exception("Error al generar el PDF del estado de cuenta: " . $e->getMessage(), 0, $e);
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
        return $pdf;
    }

    private function getDataForPDF($prestamo)
    {
        $prestamo->nombreDestino = $this->catalogoService->getCatalogo($prestamo->destino)['value'] ?? 'No especificado';
        $prestamo->nombreFrecuenciaPago = $this->catalogoService->getCatalogo($prestamo->frecuencia_pago)['value'] ?? 'No especificado';
        return $prestamo;
    }

    /**
     * Valida y normaliza la orientación del PDF
     *
     * @param string $orientation Orientación solicitada
     * @return string Orientación válida
     */
    private function validateOrientation($orientation)
    {
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
}
