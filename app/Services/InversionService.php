<?php

namespace App\Services;

use App\Constants\EstadoInversion;
use App\EstadosInversion\ControladorEstado;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use App\Models\Inversion;

class InversionService
{



    private  CuotaInversionService $cuotaInversionService;

    private ControladorEstado $controladorEstado;

    public function __construct(CuotaInversionService $cuotaInversionService, ControladorEstado $controladorEstado)
    {
        $this->cuotaInversionService = $cuotaInversionService;
        $this->controladorEstado = $controladorEstado;
    }

    public function getInversion(string $id): Inversion
    {
        return Inversion::findOrFail($id);
    }

    public function getInversiones(): Collection
    {
        return Inversion::all();
    }

    /**
     *
     * Method to create a new inversion and calculate the cuota inversion
     * @param array $inversionData
     * @return \App\Models\Inversion
     */
    public function createInversion(array $inversionData): Inversion
    {
        DB::beginTransaction();
        $inversionData['fecha'] = now();
        $inversionData['codigo'] = $this->createCode();
        $inversion = Inversion::create($inversionData);
        $this->controladorEstado->cambiarEstado($inversion, ['estado' => EstadoInversion::$CREADO]);
        DB::commit();
        return $inversion;
    }

    public function updateInversion(Inversion $inversion, array $inversionData): Inversion
    {
        $inversion->update($inversionData);
        return $inversion;
    }

    public function deleteInversion($id): void
    {
        DB::beginTransaction();
        $this->cuotaInversionService->deletePagoInversion($id);
        $inversion = Inversion::findOrFail($id);
        $inversion->delete();
        DB::commit();
    }

    public function cambiarEstado($id, $data)
    {
        DB::beginTransaction();
        $inversion = $this->getInversion($id);
        $this->controladorEstado->cambiarEstado($inversion, $data);
        DB::commit();
    }

    public function getHistoricoInversion($id)
    {
        return Inversion::findOrFail($id)->historial;
    }

    private function createCode(){
        $result = DB::select('SELECT nextval(\'correlativo_inversion\') AS correlativo');
        $correlativo = $result[0]->correlativo;
        return 'ICP-' . $correlativo;
    }
}
