<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagarCuota;
use App\Services\CuotaInversionService;
use App\Http\Resources\CuotaInversion as CuotaResource;
use App\Http\Resources\Retiro as RetiroResource;
use App\Http\Requests\DepositoRequest;
use App\Services\DepositoService;
use App\Services\CuotaHipotecaService;
use Illuminate\Http\Request;


/**
 *
 * Cuotas de Inversion
 */
class CuotaController extends Controller
{

    private $cuotaService;
    private $depositoService;

    private $cuotaHipotecaService;

    public function __construct(CuotaInversionService $cuotaService, DepositoService $depositoService, CuotaHipotecaService $cuotaHipotecaService)
    {
        $this->cuotaService =  $cuotaService;
        $this->depositoService =  $depositoService;
        $this->cuotaHipotecaService = $cuotaHipotecaService;
    }

    public function obtenerCuotasParaPagarHoy()
    {
        $cuotas = $this->cuotaService->obtenerCuotasHoy();
        return RetiroResource::collection($cuotas);
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

    public function proyectarCuota(Request $request, $id)
    {
        try {
            $proyeccion = $this->cuotaHipotecaService->proyectarCuotaAFecha($id, $request->input('fecha'));
            return response()->json(['message' => 'Proyección de cuota exitosa', 'data' => $proyeccion], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al proyectar la cuota', 'error' => $e->getMessage()], 500);
        }
    }
}
