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

    public function cambiarEstado(Prestamo_Hipotecario $prestamo, $data)
    {
        parent::cambiarEstado($prestamo, $data);
        $descripcionRetiro = "Retiro de {$prestamo->monto} para el prestamo {$prestamo->id} (codigo {$prestamo->codigo})";
        $this->retiroService->crearRetiro([
            'id_prestamo' => $prestamo->id,
            'monto' => $prestamo->monto,
            'motivo' => $descripcionRetiro,
            'tipo_cuenta_interna_id' => $data['tipo_cuenta_interna_id'],
        ]);

    }
}
