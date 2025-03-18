<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagarCuota;
use App\Services\CuotaInversionService;
use App\Http\Resources\CuotaInversion as CuotaResource;

class CuotaController extends Controller
{

    private $cuotaService;

    public function __construct(CuotaInversionService $cuotaService)
    {
        $this->cuotaService = $cuotaService;
    }

    public function pagarCuota(StorePagarCuota $request, $id)
    {
        $no_boleta = $request->no_boleta;
        $this->cuotaService->realizarPago($id, $no_boleta);
        return response()->json(['message' => 'Cuota pagada correctamente'], 200);
    }

    public function obtenerCuotasParaPagarHoy()
    {
        $cuotas = $this->cuotaService->obtenerCuotasHoy();
        return CuotaResource::collection($cuotas);
    }



}
