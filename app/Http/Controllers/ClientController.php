<?php

namespace App\Http\Controllers;

use App\Traits\Loggable;
use Illuminate\Http\Request;

use App\Services\ClientService;
use App\Services\FotografiaService;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\StoreFotografiaRequest;
use App\Http\Resources\Client as ClientResource;
use App\Http\Resources\CuentaBancaria as CuentaBancariaResource;
use App\Http\Resources\Inversion as InversionResource;
use App\Http\Resources\Reference as ReferenceResource;
use App\Http\Resources\Propiedad as PropiedadResource;
use App\Http\Resources\Prestamo as PrestamoResource;

class ClientController extends Controller
{
    use Loggable;

    private $clientService;
    private $fotografiaService;

    public function __construct(
        ClientService $clientService,
        FotografiaService $fotografiaService
    ) {
        $this->clientService = $clientService;
        $this->fotografiaService = $fotografiaService;
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

    public function prestamos(string $id)
    {
        $prestamos = $this->clientService->getPrestamos($id);
        return PrestamoResource::collection($prestamos);
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

    public function generateClientPdf($id)
    {

        $pdf = $this->clientService->generatePdf($id);
        $this->log('Generando PDF del cliente con id: ' . $id);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }
}
