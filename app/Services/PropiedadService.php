<?php


namespace App\Services;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;


class PropiedadService
{

    private $clientService;


    private $catalogoService;
    private $archivoService;


    public function __construct(
        ClientService $clientService,
        CatologoService $catalogoService,
        ArchivoService $archivoService
    ) {
        $this->clientService = $clientService;
        $this->catalogoService = $catalogoService;
        $this->archivoService = $archivoService;
    }

    public function getPropiedad(string $id): Propiedad
    {
        return Propiedad::findOrFail($id);
    }

    public function getPropiedades(): Collection
    {
        return Propiedad::all();
    }

    public function createPropiedad(array $propiedadData, $documentoSoporte): Propiedad
    {
        $this->clientService->getClient($propiedadData['dpi_cliente']);
        $garantia = Propiedad::create($propiedadData);
        if (isset($documentoSoporte)) {
            $garantia->path_documentacion = $this->guardarArchivoPrestamo($documentoSoporte, $garantia->id);
            $garantia->save();
        }
        return $garantia;
    }

    public function updatePropiedad($id, array $propiedadData): Propiedad
    {
        $propiedad = $this->getPropiedad($id);
        $propiedad->update($propiedadData);
        return $propiedad;
    }

    public function deletePropiedad(string $id): void
    {
        $propiedad = $this->getPropiedad($id);
        $propiedad->delete();
    }

    public function getDataPDF($garantia)
    {
        $garantia->nombreTipo = $this->catalogoService->getCatalogo($garantia->tipo_propiedad)['value'] ?? 'No definido';
        return $garantia;
    }


    private function guardarArchivoPrestamo($archivo, $idGarantia)
    {
        $path = 'archivos/garantias/documentacion';
        $fileName = 'garantia_' . $idGarantia . '.pdf';
        // Usar el servicio de archivo para guardar el archivo
        return $this->archivoService->guardarArchivo($archivo, $path, $fileName);
    }
}
