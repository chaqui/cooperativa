<?php

namespace App\Services;

use Carbon\Carbon;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use App\Models\Inversion;

class InversionService
{

    private  CuotaInversionService $cuotaInversionService;

    public function __construct(CuotaInversionService $cuotaInversionService)
    {
        $this->cuotaInversionService = $cuotaInversionService;
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
        $inversionData['fecha_inicio'] = now();
        $inversionData['fecha'] = $this->getFechaFinal(now(), $inversionData['plazo']);
        $inversion = Inversion::create($inversionData);
      //  $this->cuotaInversionService->calcularCuotaInversion($inversion);
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

    private function getFechaFinal($fechaInicio, $plazo)
    {

        return Carbon::parse($fechaInicio)->addDays($plazo);
    }
}
