<?php

namespace App\Services;

use App\Models\Cuenta_Bancaria;

class CuentaBancariaService {

    public function createCuentaBancaria($data) {
        $cuentaBancaria = Cuenta_Bancaria::generateCuentaBancaria($data);
        $cuentaBancaria->save();
    }

    public function updateCuentaBancaria($data, $id) {
        $cuentaBancaria = Cuenta_Bancaria::find($id);
        $cuentaBancaria->numero_cuenta = $data['numero_cuenta'];
        $cuentaBancaria->tipo_cuenta = $data['tipo_cuenta'];

        $cuentaBancaria->save();
    }

    public function deleteCuentaBancaria($id) {
        $cuentaBancaria = Cuenta_Bancaria::find($id);
        $cuentaBancaria->delete();
    }

    public function getCuentaBancaria($id) {
        return Cuenta_Bancaria::find($id);
    }

    public function getCuentasBancarias() {
        return Cuenta_Bancaria::all();
    }
}
