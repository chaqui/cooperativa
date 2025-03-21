<?php

namespace App\EstadosInversion;

use App\Constants\EstadoInversion;

use App\Models\Inversion;
use App\Services\DepositoService;

class InversionCreada extends EstadoBaseInversion
{

    /**
     * Constructor de la clase InversionCreada.
     *
     * @param DepositoService $depositoService
     */
    private $depositoService;

    public function __construct(DepositoService $depositoService)
    {
        $this->depositoService = $depositoService;
        parent::__construct(null, EstadoInversion::$CREADO);
    }

    /**
     * Cambia el estado de la inversión a APROBADO y crea un retiro.
     *
     * @param Inversion $inversion
     * @param array $data
     * @return void
     */
    public function cambiarEstado(Inversion $inversion, $data)
    {
        parent::cambiarEstado($inversion, $data);
        $this->depositoService->crearDeposito([
            'id_inversion' => $inversion->id,
            'monto' => $inversion->monto,
        ]);
    }
}
