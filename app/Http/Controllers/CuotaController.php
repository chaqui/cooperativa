<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagarCuota;
use App\Services\CuotaInversionService;
use App\Http\Resources\CuotaInversion as CuotaResource;
use App\Http\Requests\DepositoRequest;
use App\Services\DepositoService;

class CuotaController extends Controller
{

    private $cuotaService;
    private $depositoService;

    public function __construct(CuotaInversionService $cuotaService, DepositoService $depositoService)
    {
        $this->cuotaService =  $cuotaService;
        $this->depositoService =  $depositoService;
    }

    public function obtenerCuotasParaPagarHoy()
    {
        $cuotas = $this->cuotaService->obtenerCuotasHoy();
        return CuotaResource::collection($cuotas);
    }


    public function depositar(DepositoRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $deposito = $this->depositoService->depositar($id, $data);
            return response()->json(['message' => 'Deposito creado con éxito', 'data' => $deposito], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el deposito', 'error' => $e->getMessage()], 500);
        }
    }
}
