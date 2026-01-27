<?php

namespace App\Http\Controllers;

use App\Traits\Loggable;
use Illuminate\Http\Request;

use App\Services\ClientService;
use App\Services\ClientExcelService;
use App\Services\ClientChangeService;
use App\Services\FotografiaService;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\StoreFotografiaRequest;
use App\Http\Resources\Client as ClientResource;
use App\Http\Resources\CuentaBancaria as CuentaBancariaResource;
use App\Http\Resources\Beneficiario as BeneficiarioResource;
use App\Http\Resources\Inversion as InversionResource;
use App\Http\Resources\Reference as ReferenceResource;
use App\Http\Resources\Propiedad as PropiedadResource;
use App\Http\Resources\Prestamo as PrestamoResource;
use App\Http\Resources\Cuota as CuotaResource;
use App\Http\Resources\ClientChange as ClientChangeResource;

class ClientController extends Controller
{
    use Loggable;

    private $clientService;
    private $fotografiaService;
    private $clientExcelService;

    private $clientChangeService;

    public function __construct(
        ClientService $clientService,
        FotografiaService $fotografiaService,
        ClientExcelService $clientExcelService,
        ClientChangeService $clientChangeService
    ) {
        $this->clientService = $clientService;
        $this->fotografiaService = $fotografiaService;
        $this->clientExcelService = $clientExcelService;
        $this->clientChangeService = $clientChangeService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = $this->clientService->getClients();
        return ClientResource::collection($clients);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request)
    {
        $this->clientService->createClient($request->all());
        return response()->json(['message' => 'Client created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $client = $this->clientService->getClient($id);
        return new ClientResource($client);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->clientService->updateClient($request->all(), $id);
        return response()->json(['message' => 'Client updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->clientService->deleteClient($id);
        return response()->json(['message' => 'Client deleted successfully'], 200);
    }

    public function buscar(Request $request)
    {
        $searchTerm = $request->input('query', '');
        if (empty($searchTerm)) {
            return ClientResource::collection(collect());
        }
        $clients = $this->clientService->buscarClientes($searchTerm);
        return ClientResource::collection($clients);
    }

    public function inactivar(string $id)
    {
        $this->clientService->inactivarClient($id);
        return response()->json(['message' => 'Client inactivated successfully'], 200);
    }

    /**
     * Summary of cuentasBancarias
     * @param string $id    The id of the client.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function cuentasBancarias(string $id)
    {
        $cuentasBancarias = $this->clientService->getCuentasBancarias($id);
        return CuentaBancariaResource::collection($cuentasBancarias);
    }

    /**
     *
     * Summary of inversiones
     * @param string $id The id of the client.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function inversiones(string $id)
    {
        $inversiones = $this->clientService->getInversiones($id);
        return InversionResource::collection($inversiones);
    }

    /**
     *
     * Summary of referencias
     * @param string $id The id of the client.
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function referencias(string $id)
    {
        $referencias = $this->clientService->getReferencias($id);
        return ReferenceResource::collection($referencias);
    }

    public function propiedades(string $id)
    {
        $propiedades = $this->clientService->getPropiedades($id);
        return PropiedadResource::collection($propiedades);
    }

    public function getPropiedadSinPrestamo(string $id)
    {
        $propiedades = $this->clientService->getPropiedadesSinPrestamo($id);
        return PropiedadResource::collection($propiedades);
    }

    public function prestamos(string $id)
    {
        $prestamos = $this->clientService->getPrestamos($id);
        return PrestamoResource::collection($prestamos);
    }

    public function cuotas(string $id)
    {
        try {
            $cuotas = $this->clientService->getCuotas($id);
            return CuotaResource::collection($cuotas);
        } catch (\Exception $e) {
            $this->log('Error al obtener cuotas para el cliente: ' . $id . '. Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener cuotas: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Method to upload a photo, it receives a file and the id of the client, It don't validate existence of the client
     * @param \Illuminate\Http\Request $request
     * @param string $id
     * @return \App\Http\Resources\Fotografia
     */
    public function uploadFoto(StoreFotografiaRequest $request, string $id)
    {
        $foto = $request->file('fotografia');
        $path = $this->fotografiaService->uploadFotografia($foto, $id);
        return response()->json(["data" => ["path" => $path]], 200);
    }

    public function generateClientPdf($id, Request $request)
    {
        // Obtener el porcentaje de escala del request, por defecto 100%
        $scalePercentage = $request->input('scale', 100);

        // Validar que el porcentaje esté en un rango razonable
        $scalePercentage = max(50, min(200, $scalePercentage)); // Entre 50% y 200%

        $pdf = $this->clientService->generatePdf($id, $scalePercentage);
        $this->log('Generando PDF del cliente con id: ' . $id . ' con escala: ' . $scalePercentage . '%');
        return response($pdf, 200, ['Content-Type' => 'application/pdf']);
    }

    public function getBeneficiarios(string $id)
    {
        $this->log('Obteniendo beneficiarios para el cliente: ' . $id);
        $beneficiarios = $this->clientService->getBeneficiarios($id);
        return BeneficiarioResource::collection($beneficiarios);
    }

    /**
     * Obtiene los datos del cliente enriquecidos para PDF
     *
     * @param string $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataForPDF(string $id)
    {
        try {
            $this->log("Obteniendo datos enriquecidos para PDF del cliente: {$id}");

            $clientData = $this->clientService->getDataForPDF($id);

            $this->log("Datos del cliente obtenidos exitosamente para PDF");

            return response()->json([
                'message' => 'Datos del cliente obtenidos exitosamente',
                'data' => new ClientResource($clientData)
            ], 200);
        } catch (\Exception $e) {
            $this->log("Error al obtener datos del cliente para PDF: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener datos del cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    public function guardarDpi(string $dpi, Request $request)
    {
        $this->log("Guardando DPI para el cliente: {$dpi}");

        $file = $request->file('dpi_file');
        try {
            $path = $this->clientService->guardarArchivoDpi($file, $dpi);
            $this->log("DPI guardado exitosamente para el cliente: {$dpi}");
            return response()->json(['message' => 'DPI guardado exitosamente', 'data' => ['path' => $path]], 200);
        } catch (\Exception $e) {
            $this->log("Error al guardar DPI para el cliente: {$dpi}. Error: " . $e->getMessage());
            return response()->json(['message' => 'Error al guardar DPI: ' . $e->getMessage()], 500);
        }
    }

    public function exportarExcel()
    {
        $this->log('Iniciando exportación de clientes a Excel');

        $exportData = $this->clientExcelService->obtenerClientesExcel();

        $this->log('Exportación de clientes a Excel completada');

        return response($exportData['content'], 200, $exportData['headers']);
    }


    public function getChangesLog($id)
    {
        $changes = $this->clientChangeService->getClientChangesByDpi($id);
        return ClientChangeResource::collection($changes);
    }
}
