<?php

namespace App\Services;

use App\Models\Client;

use Illuminate\Support\Facades\DB;

class ClientService
{
    private $referenceService;

    private $pdfService;

    private $catalogoService;

    public function __construct(
        ReferenceService $referenceService,
        PdfService $pdfService,
        CatologoService $catalogoService
    ) {
        $this->referenceService = $referenceService;
        $this->pdfService = $pdfService;
        $this->catalogoService = $catalogoService;
    }

    /**
     *
     * Method to create a new client and its references
     * @param mixed $data data of the client
     * @return void
     */
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

    /**
     *
     * Method to update a client
     * @param mixed $data The data of the client
     * @param mixed $id The id of the client
     * @return void
     */
    public function updateClient($data, $id)
    {
        $client = $this->getClient($id);
        $client = Client::updateData($data, $client);
        $client->save();
        $references = $data['referencias'];

        foreach ($references as $reference) {
            $reference['dpi_cliente'] = $client->dpi;
            $reference = $this->referenceService->updateReference($reference['id'], $reference);
            $client->references()->save($reference);
        }
    }

    /**
     *
     * Method to delete a client
     * @param mixed $id The id of the client
     * @return void
     */
    public function deleteClient($id)
    {
        $client = Client::find($id);
        $client->delete();
    }

    /**
     * Summary of getClient
     * @param mixed $id The id of the client
     * @return mixed|\Illuminate\Database\Eloquent\Collection<int, TModel>|\Illuminate\Http\JsonResponse
     */
    public function getClient($id)
    {
        $client = Client::find($id);
        if (!$client) {
            throw new \Exception("Client not found", 404);
        }
        return $client;
    }

    /**
     * Summary of getClients
     * @return \Illuminate\Database\Eloquent\Collection<int, Client>
     */
    public function getClients()
    {
        return Client::all();
    }

    /**
     * Get the bank accounts of a client
     * @param mixed $id The id of the client
     */
    public function getCuentasBancarias($id)
    {
        $client = $this->getClient($id);
        return $client->cuentasBancarias;
    }

    /**
     *
     * Get the investments of a client
     * @param mixed $id The id of the client
     */
    public function getInversiones($id)
    {
        $client = $this->getClient($id);
        return $client->inversiones;
    }

    /**
     * Get the references of a client
     * @param mixed $id The id of the client
     * @return mixed
     */
    public function getReferencias($id): mixed
    {
        $client = $this->getClient($id);
        return $client->references;
    }

    /**
     * Get the PDF of a client
     * @param mixed $id The id of the client
     * @return void
     */
    public function generatePdf($id)
    {
        $client = $this->getClient($id);
        $client = $this->getDataByClient($client);
        $html = view('pdf.client', compact('client'))->render();
        $this->pdfService->generatePdf($html, "client-$id.pdf");
    }

    private function getDataByClient($client): Client
    {
        $client->nombreMunicipio = $this->catalogoService->getCatalogo($client->ciudad)['value'];
        $client->nombreDepartamento = $this->catalogoService->getCatalogo($client->departamento)['value'];
        $client->estadoCivil = $this->catalogoService->getCatalogo($client->estado_civil)['value'];
        $client->genero = $this->catalogoService->getCatalogo($client->genero)['value'];
        $client->nombreTipoCliente = $this->catalogoService->getCatalogo($client->tipoCliente)['value'];
        // Filtrar referencias por tipo
        $client->referenciasPersonales = $client->references->filter(function ($reference) {
            return $reference->tipo === 'personal';
        });

        $client->referenciasLaborales = $client->references->filter(function ($reference) {
            return $reference->tipo === 'laboral';
        });
        $client->referenciascomerciales = $client->references->filter(function ($reference) {
            return $reference->tipo === 'comercial';
        });
        $client->referenciasFamiliares = $client->references->filter(function ($reference) {
            return $reference->tipo === 'familiar';
        });

        return $client;
    }
}
