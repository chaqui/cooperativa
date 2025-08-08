<?php

namespace App\Services;

use App\Models\Client;

use App\Traits\Loggable;
use Illuminate\Support\Facades\DB;
use App\Constants\InicialesCodigo;
use App\Models\Beneficiario;
use App\Traits\ErrorHandler;

class ClientService extends CodigoService
{
    use Loggable;

    use ErrorHandler;
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

        $this->validar($data); // Validación temprana completa
        try {

            DB::beginTransaction();
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

            if (isset($data['beneficiarios']) && is_array($data['beneficiarios'])) {
                $this->log('Procesando ' . count($data['beneficiarios']) . ' beneficiarios');

                $this->addBeneficiarios($client, $data['beneficiarios']);
            } else {
                $this->log('No se proporcionaron beneficiarios');
            }

            DB::commit();
            $this->log('Cliente creado exitosamente id ' . $client->id . ' codigo ' . $client->codigo);

            return $client;
        } catch (\Exception $e) {
            $this->manejarError($e);
            DB::rollback();
            throw $e; // Re-lanzar la excepción para que sea capturada en las pruebas
        }
    }


    /**
     * VALIDACIÓN TEMPRANA: Valida todos los datos antes de generar código
     */
    private function validar($data)
    {
        $this->log('Iniciando validación temprana completa');


        // 1. Validar referencias si se proporcionan
        if (isset($data['referencias']) && is_array($data['referencias'])) {
            $this->validateReferencesData($data['referencias']);
        }

        // 2. Validar beneficiarios si se proporcionan
        if (isset($data['beneficiarios']) && is_array($data['beneficiarios'])) {
            $this->validateBeneficiariosData($data['beneficiarios']);
        }

        // 3. Intentar crear objeto Client sin guardarlo (validación adicional)
        $this->validateClientCreation($data);

        $this->log('✅ Validación temprana completada exitosamente');
    }

    /**
     * Validar datos de referencias
     */
    private function validateReferencesData($referencias)
    {
        $this->log('Validando ' . count($referencias) . ' referencias');

        foreach ($referencias as $index => $referencia) {
            if (empty($referencia['nombre'])) {
                $this->lanzarExcepcionConCodigo("La referencia #" . ($index + 1) . " debe tener nombre");
            }

            if (empty($referencia['telefono'])) {
                $this->lanzarExcepcionConCodigo("La referencia #" . ($index + 1) . " ({$referencia['nombre']}) debe tener teléfono");
            }

            // Validar tipo de referencia
            $tiposValidos = ['personal', 'laboral', 'comercial', 'familiar'];
            if (isset($referencia['tipo']) && !in_array($referencia['tipo'], $tiposValidos)) {
                $this->lanzarExcepcionConCodigo("Tipo de referencia inválido para {$referencia['nombre']}: {$referencia['tipo']}");
            }
        }

        $this->log('Referencias válidas');
    }

    /**
     * Validar datos de beneficiarios
     */
    private function validateBeneficiariosData($beneficiarios)
    {
        $this->log('Validando ' . count($beneficiarios) . ' beneficiarios');

        // Usar la validación existente pero de forma temprana
        $this->validateBeneficiarioPercentages($beneficiarios);

        foreach ($beneficiarios as $index => $beneficiario) {
            if (empty($beneficiario['nombre'])) {
                $this->lanzarExcepcionConCodigo("El beneficiario #" . ($index + 1) . " debe tener nombre");
            }

            if (empty($beneficiario['parentezco'])) {
                $this->lanzarExcepcionConCodigo("El beneficiario #" . ($index + 1) . " ({$beneficiario['nombre']}) debe tener parentesco");
            }
        }

        $this->log('Beneficiarios válidos');
    }

    /**
     * Validar que se puede crear el objeto Client (sin guardarlo)
     */
    private function validateClientCreation($data)
    {
        $this->log('Validando creación de objeto Cliente');

        try {
            // Intentar crear el objeto sin código (para validación)
            $datosValidacion = $data;
            unset($datosValidacion['codigo']); // Remover código para validación

            $clientValidacion = Client::generateCliente($datosValidacion);

            // Si llegamos aquí, el objeto se puede crear exitosamente
            $this->log('Objeto Cliente puede crearse exitosamente');
        } catch (\Exception $e) {
            $this->lanzarExcepcionConCodigo("Error en validación de creación de cliente: " . $e->getMessage());
        }
    }

    /**
     * Reutilizar método existente de validación de porcentajes
     */
    private function validateBeneficiarioPercentages($beneficiarios)
    {
        if (empty($beneficiarios)) {
            return;
        }

        $this->log("Validando porcentajes de " . count($beneficiarios) . " beneficiarios");

        $porcentajes = [];
        foreach ($beneficiarios as $index => $beneficiario) {
            if (!isset($beneficiario['porcentaje'])) {
                $this->lanzarExcepcionConCodigo("El beneficiario #" . ($index + 1) . " ({$beneficiario['nombre']}) no tiene porcentaje especificado.");
            }

            $porcentaje = $beneficiario['porcentaje'];

            if (!is_numeric($porcentaje)) {
                $this->lanzarExcepcionConCodigo("El porcentaje del beneficiario #" . ($index + 1) . " ({$beneficiario['nombre']}) debe ser un número válido. Valor recibido: '{$porcentaje}'");
            }

            $porcentaje = (float) $porcentaje;

            if ($porcentaje < 0 || $porcentaje > 100) {
                $this->lanzarExcepcionConCodigo("El porcentaje del beneficiario #" . ($index + 1) . " ({$beneficiario['nombre']}) debe estar entre 0 y 100. Valor recibido: {$porcentaje}%");
            }

            $porcentajes[] = $porcentaje;
        }

        $totalPorcentaje = array_sum($porcentajes);

        if (abs($totalPorcentaje - 100) > 0.01) {
            $detalles = implode(', ', array_map(function ($beneficiario, $porcentaje) {
                return "{$beneficiario['nombre']}: {$porcentaje}%";
            }, $beneficiarios, $porcentajes));

            $this->lanzarExcepcionConCodigo("La suma de los porcentajes de los beneficiarios debe ser igual a 100%. Suma actual: {$totalPorcentaje}%. Detalle: [{$detalles}]");
        }

        $this->log("Validación de porcentajes exitosa: suma total = {$totalPorcentaje}%");
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


    private function addBeneficiarios($cliente, $beneficiarios)
    {
        $this->log("Iniciando guardado de " . count($beneficiarios) . " beneficiarios para cliente DPI: " . $cliente->dpi);

        foreach ($beneficiarios as $index => $beneficiario) {
            try {
                $this->log("Procesando beneficiario #" . ($index + 1) . ": " . ($beneficiario['nombre'] ?? 'Sin nombre'));

                $beneficiario['dpi_cliente'] = $cliente->dpi;
                $beneficiarioModel = new Beneficiario($beneficiario);

                $this->log("Datos del beneficiario: " . json_encode($beneficiario));

                $result = $cliente->beneficiarios()->save($beneficiarioModel);

                $this->log("Beneficiario guardado exitosamente con ID: " . $result->id);
            } catch (\Exception $e) {
                $this->logError("Error al guardar beneficiario #" . ($index + 1) . ": " . $e->getMessage());
                $this->logError("Stack trace: " . $e->getTraceAsString());
                throw $e;
            }
        }

        // Verificar que se guardaron correctamente
        $cliente->refresh();
        $totalBeneficiarios = $cliente->beneficiarios->count();
        $this->log("Verificación final: Cliente tiene {$totalBeneficiarios} beneficiarios en total");
    }

    /**
     * Method to update a client
     * @param mixed $data The data of the client
     * @param mixed $id The id of the client
     * @return Client
     */
    public function updateClient($data, $id)
    {
        DB::beginTransaction();
        try {
            $client = $this->getClient($id);

            // Update client data
            $client = Client::updateData($data, $client);
            $client->save();
            $this->log('Cliente actualizado exitosamente id ' . $client->id);

            // Handle references - update if provided, delete if not provided
            if (isset($data['referencias']) && is_array($data['referencias'])) {
                $this->updateReferences($client, $data['referencias']);
            } else {
                // No references provided - delete all existing references
                $this->deleteAllReferences($client);
            }

            // Handle beneficiarios - update if provided, delete if not provided
            if (isset($data['beneficiarios']) && is_array($data['beneficiarios'])) {
                $this->validateBeneficiarioPercentages($data['beneficiarios']);
                $this->updateBeneficiarios($client, $data['beneficiarios']);
            } else {
                // No beneficiarios provided - delete all existing beneficiarios
                $this->deleteAllBeneficiarios($client);
            }

            DB::commit();
            $this->log('Cliente y relaciones actualizadas exitosamente');

            return $client;
        } catch (\Exception $e) {
            $this->manejarError($e);
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update references for a client
     * @param Client $client
     * @param array $referencias
     * @return void
     */
    private function updateReferences($client, $referencias)
    {
        $this->log('Procesando ' . count($referencias) . ' referencias');

        // Primero eliminar todas las referencias existentes que no están en el array
        $existingReferences = $client->references;
        $referencesIds = array_column($referencias, 'id');
        $referencesIds = array_filter($referencesIds, function ($id) {
            return $id !== null;
        });

        foreach ($existingReferences as $existingReference) {
            if (!in_array($existingReference->id, $referencesIds)) {
                $this->log("Eliminando referencia existente ID: {$existingReference->id} - {$existingReference->nombre}");
                $existingReference->delete();
            }
        }

        // Ahora procesar las referencias del array (crear nuevas o actualizar existentes)
        foreach ($referencias as $reference) {
            $reference['dpi_cliente'] = $client->dpi;

            if (isset($reference['id']) && $reference['id'] !== null) {
                // Update existing reference
                $this->log("Actualizando referencia existente ID: {$reference['id']}");
                $this->referenceService->updateReference($reference['id'], $reference);
            } else {
                // Create new reference
                $this->log("Creando nueva referencia: {$reference['nombre']}");
                $newReference = $this->referenceService->createReference($reference);
                $client->references()->save($newReference);
            }
        }

        $this->log("Referencias actualizadas exitosamente");
    }

    /**
     * Update beneficiarios for a client
     * @param Client $client
     * @param array $beneficiarios
     * @return void
     */
    private function updateBeneficiarios($client, $beneficiarios)
    {
        $this->log('Procesando ' . count($beneficiarios) . ' beneficiarios');

        // Primero eliminar todos los beneficiarios existentes que no están en el array
        $existingBeneficiarios = $client->beneficiarios;
        $beneficiariosIds = array_column($beneficiarios, 'id');
        $beneficiariosIds = array_filter($beneficiariosIds, function ($id) {
            return $id !== null;
        });

        foreach ($existingBeneficiarios as $existingBeneficiario) {
            if (!in_array($existingBeneficiario->id, $beneficiariosIds)) {
                $this->log("Eliminando beneficiario existente ID: {$existingBeneficiario->id} - {$existingBeneficiario->nombre}");
                $existingBeneficiario->delete();
            }
        }

        // Ahora procesar los beneficiarios del array (crear nuevos o actualizar existentes)
        foreach ($beneficiarios as $beneficiario) {
            $beneficiario['dpi_cliente'] = $client->dpi;

            if (isset($beneficiario['id']) && $beneficiario['id'] !== null) {
                // Update existing beneficiario
                $this->log("Actualizando beneficiario existente ID: {$beneficiario['id']}");
                $beneficiarioModel = Beneficiario::find($beneficiario['id']);
                if ($beneficiarioModel) {
                    $beneficiarioModel->update($beneficiario);
                }
            } else {
                // Create new beneficiario
                $this->log("Creando nuevo beneficiario: {$beneficiario['nombre']}");
                $beneficiarioModel = new Beneficiario($beneficiario);
                $client->beneficiarios()->save($beneficiarioModel);
            }
        }

        $this->log("Beneficiarios actualizados exitosamente");
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

    /**
     * Get the properties of a client that are not related to any loan
     * @param mixed $id The id of the client
     * @return \Illuminate\Support\Collection
     */
    public function getPropiedadesSinPrestamo($id)
    {
        $client = $this->getClient($id);
        // Assuming 'propiedades' is a relation and 'prestamos' is a relation on propiedad
        return $client->propiedades->filter(function ($propiedad) {
            // If the propiedad does not have any related prestamos
            return $propiedad->prestamos()->count() === 0;
        })->values();
    }

    /**
     * Get the loans of a client
     * @param mixed $id The id of the client
     * @return mixed
     */
    public function getPrestamos($id): mixed
    {
        $client = $this->getClient($id);
        return $client->prestamosHipotecarios;
    }

    /**
     * Get the pending quotas of a client
     * @param mixed $id The id of the client
     * @return mixed
     */
    public function getCuotas($id): mixed
    {
        $client = $this->getClient($id);
        $cuotas = $client->getCuotasPendientes();
        return $cuotas;
    }

    /**
     * Get the beneficiarios of a client
     * @param mixed $id The id of the client
     * @return mixed
     */
    public function getBeneficiarios($id): mixed
    {
        $client = $this->getClient($id);
        return $client->beneficiarios;
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

        // Cargar relaciones necesarias para el PDF
        $client->load(['references', 'beneficiarios']);

        return $this->getDataByClient($client);
    }

    private function getDataByClient($client)
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
                'tipo_vivienda' => 'tipo_vivienda'
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

            // Log de beneficiarios disponibles
            if ($client->beneficiarios && $client->beneficiarios->isNotEmpty()) {
                $this->log("El cliente tiene " . $client->beneficiarios->count() . " beneficiarios");
                foreach ($client->beneficiarios as $beneficiario) {
                    $this->log("  - {$beneficiario->nombre} ({$beneficiario->parentezco}) - {$beneficiario->porcentaje}%");
                }
            } else {
                $this->log("El cliente no tiene beneficiarios");
            }

            $this->log("Datos del cliente enriquecidos correctamente");
            return $client;
        } catch (\Exception $e) {
            $this->manejarError($e);
        }
    }

    /**
     * Delete all references for a client
     * @param Client $client
     * @return void
     */
    private function deleteAllReferences($client)
    {
        $existingReferences = $client->references;
        $countReferences = $existingReferences->count();

        if ($countReferences > 0) {
            $this->log("Eliminando {$countReferences} referencias existentes del cliente");

            foreach ($existingReferences as $reference) {
                $this->log("Eliminando referencia ID: {$reference->id} - {$reference->nombre}");
                $reference->delete();
            }

            $this->log("Todas las referencias han sido eliminadas exitosamente");
        } else {
            $this->log("El cliente no tiene referencias para eliminar");
        }
    }

    /**
     * Delete all beneficiarios for a client
     * @param Client $client
     * @return void
     */
    private function deleteAllBeneficiarios($client)
    {
        $existingBeneficiarios = $client->beneficiarios;
        $countBeneficiarios = $existingBeneficiarios->count();

        if ($countBeneficiarios > 0) {
            $this->log("Eliminando {$countBeneficiarios} beneficiarios existentes del cliente");

            foreach ($existingBeneficiarios as $beneficiario) {
                $this->log("Eliminando beneficiario ID: {$beneficiario->id} - {$beneficiario->nombre}");
                $beneficiario->delete();
            }

            $this->log("Todos los beneficiarios han sido eliminados exitosamente");
        } else {
            $this->log("El cliente no tiene beneficiarios para eliminar");
        }
    }
}
