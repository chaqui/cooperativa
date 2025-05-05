<?php

namespace App\EstadosInversion;

use App\Models\Inversion;
use App\Constants\EstadoInversion;
use App\Services\CuentaInternaService;


class InversionDepositada extends EstadoBaseInversion
{


    public function __construct()
    {
        parent::__construct(EstadoInversion::$CREADO, EstadoInversion::$DEPOSITADO);
    }

    public function cambiarEstado(Inversion $inversion, $data)
    {
        $this->log("Iniciando cambio de estado: {$inversion->id_estado} -> {$this->estadoFin}");
        $this->log("Con datos: " . json_encode($data));
        // Validar estado inicial
        if (!$data['numero_documento']) {
            throw new \Exception('El número de documento es requerido');
        }
        if (!$data['tipo_documento']) {
            throw new \Exception('El tipo de documento es requerido');
        }
        parent::cambiarEstado($inversion, $data);
    }
}
