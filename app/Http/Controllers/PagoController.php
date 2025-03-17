<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\CuotaHipotecaService;
use App\Http\Requests\StorePagarCuota;

class PagoController extends Controller
{
    private $cuotaService;

    public function __construct(CuotaHipotecaService $cuotaService)
    {
        $this->cuotaService = $cuotaService;
    }

    /**
     * Pagar cuota
     *
     * @param StorePagarCuota $request Request con los datos de la cuota
     * @param int $id ID de la cuota
     * @return \Illuminate\Http\JsonResponse
     */
    public function pagarCuota(StorePagarCuota $request, $id)
    {
        $this->cuotaService->realizarPago($request->all(), $id);
        return response()->json(['message' => 'Cuota pagada correctamente'], 200);
    }
}
