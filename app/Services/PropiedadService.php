<?php


namespace App\Services;

use App\Models\Propiedad;
use Illuminate\Database\Eloquent\Collection;


class PropiedadService
{

    private $clientService;


    private $catalogoService;


    public function __construct(
        ClientService $clientService,
        CatologoService $catalogoService
    ) {
        $this->clientService = $clientService;
        $this->catalogoService = $catalogoService;
    }

    public function getPropiedad(string $id): Propiedad
    {
        return Propiedad::findOrFail($id);
    }

    public function getPropiedades(): Collection
    {
        return Propiedad::all();
    }

    public function createPropiedad(array $propiedadData): Propiedad
    {
        $this->clientService->getClient($propiedadData['dpi_cliente']);
        return Propiedad::create($propiedadData);
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

    public function getDataPDF($garantia){
        $garantia->nombreTipo = $this->catalogoService->getCatalogo($garantia->tipo_propiedad)['value'];
        return $garantia;
    }
}
