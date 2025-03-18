<?php

namespace App\EstadosInversion;

use App\Models\Inversion;
use App\Constants\EstadoInversion;
use App\Services\CuentaInternaService;


class InversionDepositada extends EstadoBaseInversion
{

    private $cuentaInternaService;

    public function __construct(CuentaInternaService $cuentaInternaService)
    {
        $this->cuentaInternaService = $cuentaInternaService;
        parent::__construct(EstadoInversion::$CREADO, EstadoInversion::$DEPOSITADO);
    }

    public function cambiarEstado(Inversion $inversion, $data)
    {
        if (!$data['no_documento_desembolso']) {
            throw new \Exception('El número de documento es requerido');
        }
        if (!$data['tipo_documento_desembolso']) {
            throw new \Exception('El tipo de documento es requerido');
        }
        parent::cambiarEstado($inversion, $data);
        $dataCuentaInterna = [
            'ingreso' => $inversion->monto,
            'egreso' => 0,
            'descripcion' => 'Deposito de inversion con id ' . $inversion->id .
                ' con monto de ' . $inversion->monto . ' con numero de documento ' . $data['no_documento_desembolso'] .
                ' y tipo de documento ' . $data['tipo_documento_desembolso']
        ];
        $this->cuentaInternaService->createCuenta($dataCuentaInterna);
    }
}
