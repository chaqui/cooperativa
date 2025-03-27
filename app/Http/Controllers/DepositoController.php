<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositoInternoRequest;
use App\Services\DepositoService;
use Illuminate\Http\Request;

class DepositoController extends Controller
{

    private $depositoService;
    public function __construct(DepositoService $depositoService)
    {
        $this->depositoService =  $depositoService;
    }

    public function depositar(DepositoInternoRequest $request)
    {
        try {
            $data = $request->validated();
            $deposito = $this->depositoService->crearDepositoInterno($data);
            return response()->json(['message' => 'Deposito creado con éxito', 'data' => $deposito], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el deposito', 'error' => $e->getMessage()], 500);
        }
    }
}
