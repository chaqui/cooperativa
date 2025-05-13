<?php

namespace App\Services;

use App\Constants\EstadoPrestamo;
use App\EstadosPrestamo\ControladorEstado;


class PrestamoExistenService
{


    protected $controladorEstado;

    public function __construct(
        ControladorEstado $controladorEstado
    ) {
        $this->controladorEstado = $controladorEstado;
    }

    public function procesarPrestamoExistente($prestamo, $data)
    {
        $prestamo->saldo_existente = $data['saldo'];
        $prestamo->$prestamo->save();
        $this->prestamoCreado($prestamo, $data);
        $this->prestamoAutorizado($prestamo, $data);
        $this->desembolsarPrestamo($prestamo, $data);
    }

    private function prestamoCreado($prestamo, $data)
    {
        $dataEstado = [
            'razon' => 'Préstamo creado',
            'estado' => EstadoPrestamo::$CREADO,
            'fecha' => $data['fecha_creacion'],
        ];
        $this->controladorEstado->cambiarEstado($prestamo, $dataEstado);
    }

    private function prestamoAutorizado($prestamo, $data)
    {
        $dataCambionEstado = [
            'estado' => EstadoPrestamo::$APROBADO,
            'fecha' => $data['fecha_autorizacion'],
            'razones' => 'Se autorizó el préstamo automaticamente porque ya existe',
            'gastos_formalidad' => $data['gastos_formalidad'],
            'gastos_administrativos' => $data['gastos_administrativos'],
        ];
        $this->controladorEstado->cambiarEstado($prestamo, $dataCambionEstado);
    }

    private function desembolsarPrestamo($prestamo, $data)
    {

        $dataDesembolso = [
            'estado' => EstadoPrestamo::$DESEMBOLZADO,
            'razones' => 'Se desembolsó el préstamo automáticamente porque ya existe',
            'fecha' => $data['fecha_desembolso'],
            'numero_documento' => $data['numero_documento'],
            'tipo_documento' => $data['tipo_documento'],
            'cuota_pagada' => $data['cuota_pagada']
        ];
        $this->controladorEstado->cambiarEstado($prestamo, $dataDesembolso);
    }
}
