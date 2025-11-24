<?php

namespace App\EstadosInversion;

use App\Constants\EstadoInversion;
use App\Models\Inversion;
use App\Services\CuotaInversionService;

use function Symfony\Component\Clock\now;

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
        $this->log("Iniciando cambio de estado: {$inversion->id_estado} -> {$this->estadoFin}");
        $inversion->fecha_inicio = $data['fecha_inicio'] ?? now();
        parent::cambiarEstado($inversion, $data);

        $this->cuotaInversionService->createCuotas($inversion);
    }
}
