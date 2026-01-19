<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositoInternoRequest;
use App\Services\DepositoService;
use App\Http\Requests\DepositoRequest;
use App\Services\DepositoExcelService;

class DepositoController extends Controller
{

    private $depositoService;

    private $depositoExcelService;
    public function __construct(DepositoService $depositoService, DepositoExcelService $depositoExcelService)
    {
        $this->depositoService =  $depositoService;
        $this->depositoExcelService = $depositoExcelService;
    }

    /**
     * Crea un depósito interno y lo deposita
     * @param DepositoInternoRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function crearDepositoyDepositar(DepositoInternoRequest $request)
    {
        try {
            $data = $request->validated();
            $deposito = $this->depositoService->crearDepositoInterno($data);
            return response()->json(['message' => 'Deposito creado con éxito', 'data' => $deposito], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al crear el deposito', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene el PDF del depósito
     * @param mixed $id
     * @return \Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getPDF($id)
    {
        $deposito = $this->depositoService->find($id);
        if (!$deposito) {
            return response()->json(['message' => 'Deposito no encontrado'], 404);
        }

        if (!$deposito->path_pdf) {
            return response()->json(['message' => 'PDF no disponible'], 404);
        }

        return response()->download($deposito->path_pdf);
    }


    /**
     * Realiza un depósito en una cuenta específica
     * @param DepositoRequest $request
     * @param int $id ID de la cuenta donde se realizará el depósito
     * @return \Illuminate\Http\JsonResponse
     */
    public function depositar(DepositoRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['existente'] = false;
            $deposito = $this->depositoService->depositar($id, $data);
            return response()->json(['message' => 'Deposito realizado con exito', 'data' => $deposito], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error al realizar el deposito', 'error' => $e->getMessage()], 500);
        }
    }

    public function obtenerDepositosPorFecha($fecha)
    {
        try {
            $excelData = $this->depositoExcelService->generarExcelDepositosPorFecha($fecha);

            return response($excelData['content'])
                ->withHeaders($excelData['headers']);
        } catch (\Exception $e) {
            return response()->json(['message'  => $e->getMessage()], 500);
        }
    }
}
