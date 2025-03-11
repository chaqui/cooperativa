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

    public function __construct(ControladorEstado $controladorEstado, ClientService $clientService, PropiedadService $propiedadService)
    {
        $this->controladorEstado = $controladorEstado;
        $this->clientService = $clientService;
        $this->propiedadService = $propiedadService;
    }

    public function create($data)
    {
        DB::beginTransaction();
        $this->clientService->getClient($data['dpi_cliente']);
        $this->clientService->getClient($data['fiador_dpi']);
        $this->propiedadService->getPropiedad($data['propiedad_id']);



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
}
