<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInversionRequest;
use App\Http\Requests\EstadoInversionRequest;
use App\Traits\Loggable;
use Illuminate\Http\Request;

use App\Services\InversionService;
use App\Services\CuotaInversionService;
use App\Http\Resources\Deposito as DepositoResource;
use App\Http\Resources\Inversion as InversionResource;
use App\Http\Resources\CuotaInversion as CuotaResource;
use App\Http\Resources\Beneficiario as BeneficiarioResource;
use App\Http\Resources\HistoricoEstado as HistoricoEstadoResource;

class InversionController extends Controller
{
    use Loggable;
    private $inversionService;

    private $cuotaInversionService;

    public function __construct(InversionService $inversionService, CuotaInversionService $cuotaInversionService)
    {
        $this->inversionService = $inversionService;
        $this->cuotaInversionService = $cuotaInversionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inversiones = $this->inversionService->getInversiones();
        return InversionResource::collection($inversiones);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInversionRequest $request)
    {
        $archivo = $request->file('documentacion');
        if (!$archivo) {
            return response()->json(['message' => 'Archivo de documentación es requerido'], 400);
        }
        $this->inversionService->createInversion($request->all(), $archivo);
        return response()->json(['message' => 'Inversion created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $this->log("Inversion ID: $id");
        $inversion = $this->inversionService->getInversion($id);
        return new InversionResource($inversion);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $inversion = $this->inversionService->getInversion($id);
        $this->inversionService->updateInversion($inversion, $request->all());
        return response()->json(['message' => 'Inversion updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $this->inversionService->deleteInversion($id);
        return response()->json(['message' => 'Inversion deleted successfully'], 200);
    }

    public function cuotas(string $id)
    {
        $inversion = $this->inversionService->getInversion($id);
        $cuotas = $this->cuotaInversionService->getCuotasInversion($inversion);
        return CuotaResource::collection($cuotas);
    }

    public function cambiarEstado(EstadoInversionRequest $request, string $id)
    {
        $this->inversionService->cambiarEstado($id, $request->all());
        return response()->json(['message' => 'Estado changed successfully'], 200);
    }

    public function historico(string $id)
    {
        $historico = $this->inversionService->getHistoricoInversion($id);
        return HistoricoEstadoResource::collection($historico);
    }

    public function getDepositos()
    {

        $depositos = $this->inversionService->getDepositosPendientes();
        return DepositoResource::collection($depositos);
    }

    public function getDepositosInversion($id)
    {
        $this->log('Obteniendo depositos para la inversion: ' . $id);
        $depositos = $this->inversionService->getDepositos($id);
        $this->log($depositos);
        return DepositoResource::collection($depositos);
    }

    public function generatePdf($id)
    {
        return $this->inversionService->getPdf($id);
    }

    public function getExcelDePagosInversion($id)
    {
        $this->log("Generando Excel de pagos para la inversión ID: $id");
        try {
            $excelData = $this->inversionService->generarPagosInversionExistenteExcel($id);
            return response()->json($excelData, 200);
        } catch (\Exception $e) {
            $this->log("Error al generar Excel: " . $e->getMessage());
            return response()->json(['message' => 'Error al generar Excel: ' . $e->getMessage()], 500);
        }
    }

    public function procesarExcelDePagosInversion(Request $request, $id)
    {
        $this->log("Procesando Excel de pagos para la inversión ID: $id");
        try {
            $archivo = $request->file('archivo');
            if (!$archivo) {
                return response()->json(['message' => 'Archivo es requerido'], 400);
            }

            $resultado = $this->inversionService->procesarExcelPagosExistente($archivo, $id);
            return response()->json($resultado, 200);

        } catch (\Exception $e) {
            $this->log("Error al procesar Excel: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar Excel: ' . $e->getMessage()], 500);
        }
    }
}
