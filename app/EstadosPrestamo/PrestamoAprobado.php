<?php

namespace App\EstadosPrestamo;

use App\Constants\EstadoPrestamo;
use App\Models\Prestamo_Hipotecario;
use App\Services\DepositoService;
use App\Services\RetiroService;

class PrestamoAprobado extends EstadoBasePrestamo
{

    private RetiroService $retiroService;
    public function __construct(RetiroService $retiroService)
    {
        $this->retiroService = $retiroService;

        parent::__construct(EstadoPrestamo::$CREADO, EstadoPrestamo::$APROBADO);
    }

    /**
     * Cambia el estado del préstamo a APROBADO y crea un retiro asociado
     *
     * @param Prestamo_Hipotecario $prestamo El préstamo a aprobar
     * @param array $data Datos adicionales requeridos:
     *        - gastos_formalidad: (requerido) Monto de gastos de formalidad
     *        - id_cuenta: (requerido) ID del tipo de cuenta interna para el retiro
     * @throws \InvalidArgumentException Si faltan datos requeridos
     * @throws \Exception Si ocurre un error durante el cambio de estado o la creación del retiro
     */
    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        // Calcular gastos administrativos (1% del monto)
        $prestamo->gastos_administrativos = $prestamo->existente ? $data['gastos_administrativos'] : $prestamo->monto * 0.01;
        $prestamo->gastos_formalidad = $data['gastos_formalidad'];

        // Cambiar el estado utilizando la lógica del padre
        parent::cambiarEstado($prestamo, $data);
        if (!$prestamo->existente) {
            $this->generarRetiro($prestamo, $data);
        }
    }

    private function generarRetiro($prestamo, $data)
    {
        // Calcular monto neto del préstamo (monto - gastos)
        $montoDeposito = $prestamo->monto - $prestamo->gastos_administrativos - $prestamo->gastos_formalidad;
        if ($montoDeposito <= 0) {
            throw new \InvalidArgumentException(
                "El monto a retirar debe ser positivo. Gastos excesivos: gastos administrativos + " .
                    "gastos de formalidad superan el monto del préstamo"
            );
        }

        $descripcionRetiro = sprintf(
            "Retiro de Q%.2f para el préstamo #%d (código: %s)",
            $montoDeposito,
            $prestamo->id,
            $prestamo->codigo
        );

        // Crear el retiro asociado
        $this->retiroService->crearRetiro([
            'id_prestamo' => $prestamo->id,
            'monto' => $montoDeposito,
            'monto_total' => $prestamo->monto,
            'motivo' => $descripcionRetiro,
            'id_cuenta' => $data['id_cuenta'],
        ]);
    }
}
