<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositoInternoRequest;
use App\Http\Requests\RetiroRequest;

use App\Services\RetiroService;

class RetiroController extends Controller
{
    private $retiroService;
    public function __construct(RetiroService $retiroService)
    {
        $this->retiroService = $retiroService;
    }

    public function retirar(RetiroRequest $request, $id)
    {

        $this->retiroService->realizarRetiro($id, $request->all());

        return response()->json(['message' => 'Retiro procesado con éxito'], 200);
    }

    public function crearRetiro(DepositoInternoRequest $request)
    {
        $this->retiroService->crearRetiroInterno($request->all());

        return response()->json(['message' => 'Retiro interno creado con éxito'], 200);
    }

    public function getPdf($id)
    {
        return $this->retiroService->getPdf($id);
    }
}
