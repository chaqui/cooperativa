<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\Impuesto as ImpuestoResource;
use App\Http\Resources\Declaracion as DeclaracionResource;
use App\Services\TipoImpuestoService;

class ImpuestoController extends Controller
{
    private $tipoImpuestoService;

    public function __construct(TipoImpuestoService $tipoImpuestoService)
    {
        $this->tipoImpuestoService = $tipoImpuestoService;
    }
    public function index()
    {
        $tipoImpuestos = $this->tipoImpuestoService->getTiposImpuestos();
        return ImpuestoResource::collection($tipoImpuestos);
    }

    public function getDeclaraciones($id)
    {
        $declaraciones = $this->tipoImpuestoService->getDeclaraciones($id);

        return DeclaracionResource::collection($declaraciones);
    }
}
