<?php

namespace App\Services;

use App\Constants\EstadoInversion;
use App\Models\Deposito;
use Illuminate\Support\Facades\DB;

class DepositoService
{

    /**
     * Crea un nuevo depósito.
     *
     * @param array $datos
     * @return Deposito
     */
    public function crearDeposito(array $datos)
    {
        $deposito = new Deposito();
        $deposito->tipo_documento = $datos['tipo_documento']?? null;
        $deposito->monto = $datos['monto'];
        $deposito->id_inversion = $datos['id_inversion'] ?? null;
        $deposito->id_pago = $datos['id_pago'] ?? null;
        $deposito->realizado = false;
        $deposito->save();
        if ($deposito->id_pago) {
           $this->depositar($deposito->id, $datos);
        }
        return $deposito;
    }

    public function getDeposito($id)
    {
        return Deposito::findOrFail($id);
    }
    public function getDepositos()
    {
        return Deposito::all();
    }

    public function depositar($id, $data)
    {
        DB::beginTransaction();
        $deposito = $this->getDeposito($id);
        if ($deposito->realizado) {
            throw new \Exception('El depósito ya ha sido realizado.');
        }
        $deposito->realizado = true;
        $deposito->tipo_documento = $data['tipo_documento'];
        $deposito->numero_documento = $data['numero_documento'];
        $deposito->imagen = $data['imagen']?? null;
        $deposito->save();
        if ($deposito->id_inversion) {
            $inversionService = app(InversionService::class);
            $data['estado'] = EstadoInversion::$DEPOSITADO;
            $inversionService->cambiarEstado($deposito->id_inversion, $data);
        }
        DB::commit();
        return $deposito;
    }
}
