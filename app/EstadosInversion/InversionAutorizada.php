<?php

namespace App\EstadosInversion;

use App\Constants\EstadoInversion;
use App\Models\Inversion;
use App\Services\CuotaInversionService;

class InversionAutorizada extends EstadoBaseInversion
{

    private CuotaInversionService $cuotaInversionService;
    public function __construct(CuotaInversionService $cuotaInversionService)
    {
        $this->cuotaInversionService = $cuotaInversionService;
        parent::__construct(EstadoInversion::$DEPOSITADO, EstadoInversion::$APROBADO);
    }

    public function cambiarEstado(Inversion $inversion, $data)
    {
        $inversion->fecha_inicio = now();
        parent::cambiarEstado($inversion, $data);

        $this->cuotaInversionService->createCuotas($inversion);
    }
}
