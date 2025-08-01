<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstadoRequest;
use App\Http\Requests\PrestamoRequest;
use App\Http\Resources\Prestamo as PrestamoResource;
use App\Http\Resources\HistoricoEstado as HistoricoEstadoResource;
use App\Http\Resources\Cuota as PagoResource;
use App\Http\Resources\Retiro as RetiroResource;
use App\Http\Requests\StorePagarCuota;

use App\Services\PrestamoService;
use App\Services\PrestamoPdfService;
use App\Services\EstadosPrestamoService;
use App\Services\PrestamoExcelService;
use App\Traits\Loggable;

class PrestamoController extends Controller
{

    private $prestamoService;

    private $prestamoPdfService;

    private $estadosPrestamoService;

    private $prestamoExcelService;

    use Loggable;

    public function __construct(
        PrestamoService $prestamoService,
        PrestamoPdfService $prestamoPdfService,
        EstadosPrestamoService $estadosPrestamoService,
        PrestamoExcelService $prestamoExcelService
    ) {
        $this->prestamoService = $prestamoService;
        $this->prestamoPdfService = $prestamoPdfService;
        $this->estadosPrestamoService = $estadosPrestamoService;
        $this->prestamoExcelService = $prestamoExcelService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return PrestamoResource::collection($this->prestamoService->all());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PrestamoRequest $request)
    {
        $prestamo = $this->prestamoService->create($request->all());
        return new PrestamoResource($prestamo);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $prestamo = $this->prestamoService->get($id);
        return new PrestamoResource($prestamo);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrestamoRequest $request, string $id)
    {
        return PrestamoResource::collection($this->prestamoService->update($id, $request->all()));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->prestamoService->delete($id);
    }

    public function cambiarEstado(EstadoRequest $request, string $id)
    {
        $this->estadosPrestamoService->cambiarEstado($id, $request->all());
        return response()->json(['message' => 'Estado cambiado correctamente'], 200);
    }

    public function prestamosByEstado(string $estado)
    {
        return PrestamoResource::collection($this->estadosPrestamoService->getPrestamosByEstado($estado));
    }

    public function historial(string $id)
    {
        return HistoricoEstadoResource::collection($this->estadosPrestamoService->getHistorial($id));
    }

    public function generatePdf(string $id)
    {
        $pdf = $this->prestamoPdfService->generatePdf($id);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function pagos(string $id)
    {
        return PagoResource::collection($this->prestamoService->getPagos($id));
    }

    public function getRetirosPendientes()
    {
        return RetiroResource::collection($this->prestamoService->getRetirosPendientes());
    }

    public function generarEstadoCuenta(string $id)
    {
        $orientation = request()->query('orientation', 'landscape');
        $pdf = $this->prestamoPdfService->generarEstadoCuentaPdf($id, false, $orientation);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function getEstadoCuenta(string $id)
    {
        $prestamo = $this->prestamoService->get($id);
        if (!$prestamo->estado_cuenta_path) {
            $this->log('No se ha generado el estado de cuenta para el préstamo: ' . $id);
            return response()->json(['message' => 'No se ha generado el estado de cuenta'], 404);
        }
        return response()->download($prestamo->estado_cuenta_path);
    }

    public function pagarCuota(StorePagarCuota $request, string $id)
    {
        $this->prestamoService->pagarCuota($id, $request->all());
        return response()->json(['message' => 'Pago realizado correctamente'], 200);
    }



    /**
     * Genera y descarga un Excel con un préstamo específico
     */
    public function downloadExcelPrestamo()
    {
        try {

            $excelData = $this->prestamoExcelService->generateExcel();

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);

        } catch (\Exception $e) {
            $this->log('Error al generar el archivo Excel: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al generar el archivo Excel: ' . $e->getMessage()
            ], 500);
        }
    }

}
