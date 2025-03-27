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
        // Verificar que la inversión tenga monto
        if (!$inversion->monto || $inversion->monto <= 0) {
            throw new \InvalidArgumentException(
                "La inversión #{$inversion->id} debe tener un monto válido para crear el depósito"
            );
        }

        parent::cambiarEstado($inversion, $data);
        // Generar descripción detallada para el depósito
        $descripcion = sprintf(
            'Depósito inicial de la inversión #%d (Código: %s) por monto: Q%s',
            $inversion->id,
            $inversion->codigo ?? 'No especificado',
            number_format($inversion->monto, 2)
        );

        // Crear el depósito asociado
        $this->depositoService->crearDeposito([
            'id_inversion' => $inversion->id,
            'monto' => $inversion->monto,
            'motivo' => $descripcion,
            'fecha' => now()
        ]);
    }
}
