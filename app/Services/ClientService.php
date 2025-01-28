<?php

namespace App\Services;

use App\Models\Client;

use Illuminate\Support\Facades\DB;

class ClientService
{
    private $referenceService;

    public function __construct(ReferenceService $referenceService)
    {
        $this->referenceService = $referenceService;
    }

    public function createClient($data)
    {
        DB::beginTransaction();
        $client = Client::generateCliente($data);

        $client->save();
        $references = $data['referencias'];

        foreach ($references as $reference) {
            $reference['dpi_cliente'] = $client->dpi;
            $reference = $this->referenceService->createReference($reference);
            $client->references()->save($reference);
        }

        DB::commit();
    }

    public function updateClient($data, $id)
    {
        $client = Client::find($id);
        $client->name = $data['name'];
        $client->email = $data['email'];
        $client->phone = $data['phone'];
        $client->save();
    }

    public function deleteClient($id)
    {
        $client = Client::find($id);
        $client->delete();
    }

    public function getClient($id)
    {
        $client = Client::find($id);
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }
        return $client;
    }

    public function getClients()
    {
        return Client::all();
    }

    public function getCuentasBancarias($id)
    {
        $client = $this->getClient($id);
        return $client->cuentasBancarias;
    }

    public function getInversiones($id)
    {
        $client = $this->getClient($id);
        return $client->inversiones;
    }

    public function getReferencias($id): mixed
    {
        $client = $this->getClient($id);
        \Log::info($client);
        return $client->references;
    }
}
