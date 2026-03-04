<?php

namespace App\Services;

use App\Models\DepositoHistoricoSaldo;
use App\Traits\ErrorHandler;
use App\Traits\RegistrarRollback;
use App\Constants\RollBackCampos;

class DepositoHistoricoSaldoService
{
    use ErrorHandler;

    use RegistrarRollback;

    public function crearRegistro($idDeposito, $idHistoricoSaldo, $idPrestamoHipotecario)
    {
        try {
            $this->log("Creando registro en DepositoHistoricoSaldo para deposito ID: {$idDeposito} y historico saldo ID: {$idHistoricoSaldo}");

            $depositoHistorico = DepositoHistoricoSaldo::createHistoricoSaldo($idDeposito, $idHistoricoSaldo);
            $depositoHistorico->save();
            $this->agregarDatosEliminar($idPrestamoHipotecario, $depositoHistorico->id, RollBackCampos::$depositoHistoricoSaldo);
            $this->log("Registro en DepositoHistoricoSaldo creado con ID: {$depositoHistorico->id}");
        } catch (\Exception $e) {
            $this->log("Error al crear registro en DepositoHistoricoSaldo: " . $e->getMessage());
        }
    }
}
