<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\ClientService;
use App\Http\Requests\StoreClientRequest;
use App\Http\Resources\Client as ClientResource;
use App\Http\Resources\CuentaBancaria as CuentaBancariaResource;
use App\Http\Resources\Inversion as InversionResource;

class ClientController extends Controller
{

    private $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
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
    public function create()
    {
    }

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
    public function edit(string $id)
    {

    }

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

    public function cuentasBancarias(string $id)
    {
        $cuentasBancarias = $this->clientService->getCuentasBancarias($id);
        return CuentaBancariaResource::collection($cuentasBancarias);
    }

    public function inversiones(string $id)
    {
        $inversiones = $this->clientService->getInversiones($id);
        return InversionResource::collection($inversiones);
    }
}
