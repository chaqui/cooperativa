<?

namespace App\Services;


use App\Models\Prestamo_Hipotecario;
use App\EstadosPrestamo\ControladorEstado;

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
     * @return mixed PDF generado
     * @throws \Exception Si ocurre un error durante el proceso
     */
    public function generarEstadoCuentaPdf($id, $inicial = false)
    {
        try {
            $this->log("Generando PDF del estado de cuenta para el préstamo: {$id}");
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
            ])->render();

            // Generar el PDF
            $pdf = $this->pdfService->generatePdf($html, 'landscape');

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


    public function generatePdf($id)
    {
        $this->log('Generando PDF del prestamo con id: ' . $id);
        $prestamo = $this->get($id);
        $prestamo = $this->getDataForPDF($prestamo);
        $prestamo->cliente = $this->clientService->getDataForPDF($prestamo->dpi_cliente);
        $prestamo->propiedad = $this->propiedadService->getDataPDF($prestamo->propiedad);
        if ($prestamo->fiador_dpi != null) {
            $prestamo->fiador = $this->clientService->getDataForPDF($prestamo->fiador_dpi);
        }
        $html = view('pdf.prestamo', data: compact('prestamo'))->render();
        $pdf = $this->pdfService->generatePdf($html);
        return $pdf;
    }

    private function getDataForPDF($prestamo)
    {
        $prestamo->nombreDestino = $this->catalogoService->getCatalogo($prestamo->destino)['value'] ?? 'No especificado';
        $prestamo->nombreFrecuenciaPago = $this->catalogoService->getCatalogo($prestamo->frecuencia_pago)['value'] ?? 'No especificado';
        return $prestamo;
    }
}
