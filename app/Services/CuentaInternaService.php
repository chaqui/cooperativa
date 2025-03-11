<?php

namespace App\Services;

use App\Models\Cuenta_Interna;

class CuentaInternaService
{
    public function getAllCuentas()
    {
        return Cuenta_Interna::all();
    }

    public function createCuenta(array $data)
    {
        if (!isset($data['ingreso']) || !isset($data['egreso'])) {
            throw new \Exception('Ingreso, egreso son requeridos');
        }

        if (!is_numeric($data['ingreso']) || !is_numeric($data['egreso'])) {
            throw new \Exception('Ingreso, egreso deben ser numericos');
        }
        if ($data['ingreso'] < 0 || $data['egreso'] < 0) {
            throw new \Exception('Ingreso, egreso deben ser mayores a 0');
        }
        if (!isset($data['descripcion'])) {
            throw new \Exception('Descripcion es requerida');
        }
        if ($data['descripcion'] == '') {
            throw new \Exception('Descripcion no puede estar vacia');
        }
        return Cuenta_Interna::create($data);
    }
}
