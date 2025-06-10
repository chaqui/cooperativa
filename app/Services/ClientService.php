<?php

namespace App\Services;

use App\Models\Client;

use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Constants\InicialesCodigo;

class ClientService extends CodigoService
{
    use Loggable;
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
        parent::__construct(InicialesCodigo::$Cliente);
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
        try {
            // Generar código único para el cliente
            $data['codigo'] = $this->createCode();
            $this->log('Código generado para cliente ' . $data['codigo']);

            // Crear el cliente
            $client = Client::generateCliente($data);
            $client->save();


            // Crear las referencias
            if (isset($data['referencias']) && is_array($data['referencias'])) {
                $this->log('Procesando ' . count($data['referencias']) . ' referencias');
                $this->addReferences($client, $data['referencias']);
            } else {
                $this->log('No se proporcionaron referencias');
            }

            DB::commit();
            $this->log('Cliente creado exitosamente id ' . $client->id . ' codigo ' . $client->codigo);

            return $client;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->logError('Error al crear cliente: ' . $e->getMessage());

            throw new \Exception('No se pudo crear el cliente: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Add the references to the client
     * @param mixed $client The client
     * @param mixed $references List of references
     * @return void
     */
    private function addReferences($client, $references)
    {
        foreach ($references as $reference) {
            $reference['dpi_cliente'] = $client->dpi;
            $reference = $this->referenceService->createReference($reference);
            $client->references()->save($reference);
        }
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
            $this->log($reference);
            $reference['dpi_cliente'] = $client->dpi;
            if (isset($reference['id']) && $reference['id'] !== null) {
                 $reference = $this->referenceService->updateReference($reference['id'], $reference);
            } else {
                // Create new reference
                $reference = $this->referenceService->createReference($reference);
            }

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

    public function inactivarClient($id)
    {
        $client = Client::find($id);
        $client->etado_cliente = 2;
        $client->save();
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
     * Get the properties of a client
     * @param mixed $id The id of the client
     * @return mixed
     */
    public function getPropiedades($id): mixed
    {
        $client = $this->getClient($id);
        return $client->propiedades;
    }

    public function getPrestamos($id): mixed
    {
        $client = $this->getClient($id);
        return $client->prestamosHipotecarios;
    }

    public function getCuotas($id): mixed
    {
        $client = $this->getClient($id);
        $cuotas = $client->getCuotasPendientes();
        return $cuotas;
    }

    /**
     * Get the PDF of a client
     * @param mixed $id The id of the client
     * @return void
     */
    public function generatePdf($id)
    {
        $client = $this->getDataForPDF($id);
          $this->log($client);
        $html = view('pdf.client', data: compact('client'))->render();

        $pdf = $this->pdfService->generatePdf($html);
        return $pdf;
    }

    public function getDataForPDF($id)
    {
        $client = $this->getClient($id);
        return $this->getDataByClient($client);
    }

    private function getDataByClient($client): Client
    {
        $this->log("Iniciando enriquecimiento de datos para cliente #{$client->id}");

        try {
            // Enriquecer con datos de catálogos
            $catalogosAEnriquecer = [
                'ciudad' => 'nombreMunicipio',
                'departamento' => 'nombreDepartamento',
                'estado_civil' => 'estadoCivil',
                'genero' => 'genero',
                'tipoCliente' => 'nombreTipoCliente',
                'tipo_vivienda' => 'casa_donde_vive'
            ];

            foreach ($catalogosAEnriquecer as $codigoCatalogo => $nombrePropiedad) {
                if (!empty($client->$codigoCatalogo)) {
                    try {
                        $catalogo = $this->catalogoService->getCatalogo($client->$codigoCatalogo);
                        $client->$nombrePropiedad = $catalogo['value'] ?? "No especificado";
                    } catch (\Exception $e) {
                        $this->logError("Error al obtener catálogo {$codigoCatalogo}: " . $e->getMessage());
                        $client->$nombrePropiedad = "No disponible";
                    }
                } else {
                    $client->$nombrePropiedad = "No especificado";
                }
            }

            // Clasificar referencias por tipo si existen
            if ($client->references && $client->references->isNotEmpty()) {
                $tiposReferencia = ['personal', 'laboral', 'comercial', 'familiar'];

                foreach ($tiposReferencia as $tipo) {
                    // Corregir el formato de nombres de propiedad para ser consistente
                    if ($tipo === 'comercial') {
                        $propiedadReferencias = 'referenciasComerciales'; // Corregido: C mayúscula y 'es' al final
                    } else {
                        $propiedadReferencias = 'referencias' . ucfirst($tipo) . 'es';
                    }

                    // Filtrar las referencias por tipo y convertirlas en array para serialización JSON
                    $referenciasFiltradas = $client->references->filter(function ($reference) use ($tipo) {
                        return $reference->tipo === $tipo;
                    })->values(); // Reindexar el array resultante

                    // Asignar como array y no como colección
                    $client->$propiedadReferencias = $referenciasFiltradas->toArray();

                    $this->log("Referencias de tipo {$tipo}: " . count($client->$propiedadReferencias));
                }
            } else {
                $this->log("El cliente no tiene referencias");
                // Inicializar arrays vacíos para evitar errores
                $client->referenciasPersonales = [];
                $client->referenciasLaborales = [];
                $client->referenciasComerciales = []; // Corregido: C mayúscula y 'es' al final
                $client->referenciasFamiliares = [];
            }

            $this->log("Datos del cliente enriquecidos correctamente");
            return $client;
        } catch (\Exception $e) {
            $this->logError("Error al enriquecer datos del cliente: " . $e->getMessage());
            throw new \Exception("Error al procesar datos del cliente: " . $e->getMessage(), 0, $e);
        }
    }
}
