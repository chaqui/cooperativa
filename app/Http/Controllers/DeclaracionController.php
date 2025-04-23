<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\TransaccionImpuesto as TransaccionImpuestoResource;
use App\Http\Requests\DeclaracionRequest;
use App\Http\Resources\Declaracion;
use App\Services\DeclaracionImpuestoService;
use App\Http\Resources\Declaracion as DeclaracionResource;

class DeclaracionController extends Controller
{
    private DeclaracionImpuestoService $declaracionImpuestoService;
    public function __construct(DeclaracionImpuestoService $declaracionImpuestoService)
    {
        $this->declaracionImpuestoService = $declaracionImpuestoService;
    }

    public function getTransacciones($id)
    {
        $transacciones = $this->declaracionImpuestoService->getTransacciones($id);

        return TransaccionImpuestoResource::collection($transacciones);
    }

    public function declarar($id, DeclaracionRequest $declaracion)
    {
        $declaracion = $this->declaracionImpuestoService->declararImpuesto($id, $declaracion);

        return response()->json([
            'message' => 'Declaración realizada con éxito',
            'data' => new Declaracion($declaracion)
        ], 201);
    }

    public function show($id)
    {
        $declaracion = $this->declaracionImpuestoService->getDeclaracionImpuesto($id);
        return new DeclaracionResource($declaracion);
    }
}
