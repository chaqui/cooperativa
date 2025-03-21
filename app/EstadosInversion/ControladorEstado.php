<?php

namespace App\EstadosInversion;

use App\Models\Inversion;
use App\Constants\EstadoInversion;
use App\Services\CuentaInternaService;
use App\Services\CuotaInversionService;
use App\Services\DepositoService;
use App\Traits\Loggable;

class ControladorEstado
{
    use Loggable;
    private CuentaInternaService $cuentaInternaService;

    private CuotaInversionService $cuotaInversionService;

    private DepositoService $depositoService;

    public function __construct(
        CuentaInternaService $cuentaInternaService,
        CuotaInversionService $cuotaInversionService,
        DepositoService $depositoService
    ) {
        $this->cuentaInternaService = $cuentaInternaService;
        $this->cuotaInversionService = $cuotaInversionService;
        $this->depositoService = $depositoService;
    }
    public function cambiarEstado(Inversion $inversion, $data)
    {
        $estado = self::getEstado($data['estado']);
        $estado->cambiarEstado($inversion, $data);
    }

    private function getEstado($estado)
    {
        $this->log("Estado: $estado");
        switch ($estado) {
            case EstadoInversion::$CREADO:
                return new InversionCreada($this->depositoService);
            case EstadoInversion::$DEPOSITADO:
                return new InversionDepositada($this->cuentaInternaService);
            case EstadoInversion::$APROBADO:
                return new InversionAutorizada($this->cuotaInversionService);
            default:
                throw new \Exception("El estado no es correcto");
        }
    }
}
