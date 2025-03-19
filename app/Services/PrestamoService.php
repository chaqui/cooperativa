<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\EstadosPrestamo\ControladorEstado;
use App\Models\Prestamo_Hipotecario;
use Illuminate\Support\Facades\DB;

class PrestamoService
{

    private $controladorEstado;

    private $clientService;

    private $propiedadService;

    private $catalogoService;


    private $pdfService;

    private $userService;

    public function __construct(ControladorEstado $controladorEstado, ClientService $clientService, PropiedadService $propiedadService, CatologoService $catalogoService, PdfService $pdfService, UserService $userService)
    {
        $this->controladorEstado = $controladorEstado;
        $this->clientService = $clientService;
        $this->propiedadService = $propiedadService;
        $this->catalogoService = $catalogoService;
        $this->pdfService = $pdfService;
        $this->userService = $userService;
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
        return Prestamo_Hipotecario::find($id);
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
        $prestamo->nombreDestino = $this->catalogoService->getCatalogo($prestamo->destino)['value'];
        $prestamo->nombreFrecuenciaPago = $this->catalogoService->getCatalogo($prestamo->frecuencia_pago)['value'];
        return $prestamo;
    }

    public function getPagos($id)
    {
        $prestamoHipotecario = $this->get($id);
        return $prestamoHipotecario->pagos;
    }

    private function createCode(){
        $result = DB::select('SELECT nextval(\'correlativo_prestamo\') AS correlativo');
        $correlativo = $result[0]->correlativo;
        return 'PCP-' . $correlativo;
    }
}
