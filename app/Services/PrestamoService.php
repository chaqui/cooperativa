<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\EstadosPrestamo\ControladorEstado;
use App\Models\Prestamo_Hipotecario;
use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;

use App\Services\CuotaHipotecaService;
use App\Services\ClientService;
use App\Services\PropiedadService;
use App\Services\CatologoService;
use App\Services\PdfService;
use App\Services\UserService;
use Exception;


class PrestamoService
{

    use Loggable;
    private $controladorEstado;

    private $clientService;

    private $propiedadService;

    private $catalogoService;


    private $pdfService;

    private $userService;


    private  $cuotaHipotecaService;
    public function __construct(
        ControladorEstado $controladorEstado,
        ClientService $clientService,
        PropiedadService $propiedadService,
        CatologoService $catalogoService,
        PdfService $pdfService,
        UserService $userService,
        CuotaHipotecaService $cuotaHipotecaService
    ) {
        $this->controladorEstado = $controladorEstado;
        $this->clientService = $clientService;
        $this->propiedadService = $propiedadService;
        $this->catalogoService = $catalogoService;
        $this->pdfService = $pdfService;
        $this->userService = $userService;
        $this->cuotaHipotecaService = $cuotaHipotecaService;
    }

    public function create($data)
    {
        DB::beginTransaction();
        $this->clientService->getClient($data['dpi_cliente']);
        $this->clientService->getClient($data['fiador_dpi']);
        $this->propiedadService->getPropiedad($data['propiedad_id']);
        $data['codigo'] = $this->createCode();

        $usuario = $this->userService->getUserOfToken();
        $data['id_usuario'] = $usuario->id;
        $prestamo = Prestamo_Hipotecario::create($data);
        $dataEstado = [
            'razon' => 'Prestamo creado',
            'estado' => EstadoPrestamo::$CREADO
        ];
        $this->controladorEstado->cambiarEstado($prestamo,  $dataEstado);
        DB::commit();
    }

    public function update($id, $data)
    {
        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->update($data);
        return $prestamo;
    }

    public function delete($id)
    {
        $prestamo = Prestamo_Hipotecario::find($id);
        $prestamo->delete();
    }

    public function get($id)
    {
        $this->log('Buscando prestamo con id: ' . $id);
        $prestamo = Prestamo_Hipotecario::find($id);
        if (!$prestamo) {
            $this->log('Prestamo no encontrado con id: ' . $id);
            throw new Exception('Prestamo no encontrado con id: ' . $id);
        }
        $this->log('Prestamo encontrado con id: ' . $id);
        return $prestamo;
    }

    public function all()
    {
        return Prestamo_Hipotecario::all();
    }

    public function cambiarEstado($id, $data)
    {
        DB::beginTransaction();
        $prestamo = $this->get($id);
        $this->controladorEstado->cambiarEstado($prestamo, $data);
        DB::commit();
    }

    public function getPrestamosByEstado($estado)
    {
        return Prestamo_Hipotecario::where('estado_id', $estado)->get();
    }

    public function getHistorial($id)
    {
        $prestamo = $this->get($id);
        return $prestamo->historial;
    }

    public function generatePdf($id)
    {
        $this->log('Generando PDF del prestamo con id: ' . $id);
        $prestamo = $this->get($id);
        $prestamo = $this->getDataForPDF($prestamo);
        $prestamo->cliente = $this->clientService->getDataForPDF($prestamo->dpi_cliente);
        $prestamo->propiedad = $this->propiedadService->getDataPDF($prestamo->propiedad);
        $prestamo->fiador = $this->clientService->getDataForPDF($prestamo->fiador_dpi);
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

    public function getPagos($id)
    {
        $prestamoHipotecario = $this->get($id);
        return $prestamoHipotecario->pagos;
    }

    private function createCode()
    {
        $result = DB::select('SELECT nextval(\'correlativo_prestamo\') AS correlativo');
        $correlativo = $result[0]->correlativo;
        return 'PCP-' . $correlativo;
    }

    public function getRetirosPendientes()
    {
        $this->log('Iniciando búsqueda de retiros pendientes');
        $prestamos = $this->all();
        $retirosPendientes = collect();
        foreach ($prestamos as $prestamo) {
            if (!$prestamo->retiro) {
                continue;
            }
            $retiro = $prestamo->retiro;

            $retiro->codigo_prestamo = $prestamo->codigo;
            $retiro->nombreCliente = $prestamo->cliente->getFullNameAttribute();
            $retiro->gastosAdministrativos = $prestamo->gastos_administrativos;
            $retiro->gastosFormalidad = $prestamo->gastos_formalidad;


            $retirosPendientes->push($retiro);
        }
        $this->log('Retiros pendientes obtenidos: ' . $retirosPendientes->count());
        return $retirosPendientes;
    }

    public function pagarCuota($id, $data)
    {
        DB::beginTransaction();
        try {
            $prestamo = $this->get($id);
            $cuotaApagar = $prestamo->cuotaActiva();

            if (!$cuotaApagar) {
                $this->logError("No hay cuotas activas para el préstamo: {$prestamo->codigo}");
                throw new Exception("No hay cuotas activas para el préstamo {$prestamo->codigo}");
            }
            $this->log('Realizando pago de cuota' . $cuotaApagar->numero_pago_prestamo . ' del prestamo : ' . $prestamo->codigo);
            $cuotaHipotecaService = app(CuotaHipotecaService::class);
            $cuotaHipotecaService->realizarPago($data, $cuotaApagar->id);

            DB::commit();
            $this->log('Pago realizado con éxito para la cuota: ' . $cuotaApagar->id);
        } catch (Exception $e) {
            DB::rollBack();
            $this->log('Error al realizar el pago: ' . $e->getMessage());
            throw $e;
        }
    }

    public function generarEstadoCuentaPdf($id)
    {
        $prestamo = $this->get($id);
        $prestamo->totalPagado = $prestamo->totalPagado();
        $prestamo->saldoPendiente = $prestamo->saldoPendiente();
        $pagos = $prestamo->pagos;

        $html = view('pdf.estadoCuenta', [
            'prestamo' => $prestamo,
            'pagos' => $pagos,
        ])->render();
        $pdf = $this->pdfService->generatePdf($html, 'landscape');
        return $pdf;
    }
}
