<?php

namespace App\Http\Controllers;

use App\Http\Requests\EstadoRequest;
use App\Http\Requests\PrestamoRequest;
use Illuminate\Http\Request;
use App\Services\PrestamoService;
use App\Http\Resources\Prestamo as PrestamoResource;
use App\Http\Resources\HistoricoEstado as HistoricoEstadoResource;
use App\Http\Resources\Cuota as PagoResource;

class PrestamoController extends Controller
{

    private $prestamoService;

    public function __construct(PrestamoService $prestamoService)
    {
        $this->prestamoService = $prestamoService;
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
        $this->prestamoService->create($request->all());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return $this->prestamoService->get($id);
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
        $this->prestamoService->cambiarEstado($id, $request->all());
    }

    public function prestamosByEstado(string $estado)
    {
        return PrestamoResource::collection($this->prestamoService->getPrestamosByEstado($estado));
    }

    public function historial(string $id)
    {
        return HistoricoEstadoResource::collection($this->prestamoService->getHistorial($id));
    }

    public function generatePdf(string $id)
    {
        $pdf = $this->prestamoService->generatePdf($id);
        return response($pdf, 200)->header('Content-Type', 'application/pdf');
    }

    public function pagos(string $id)
    {
        return PagoResource::collection($this->prestamoService->getPagos($id));
    }


}
