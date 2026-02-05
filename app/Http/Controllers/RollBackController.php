<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RollBackService;
use App\Http\Resources\RollBackResource;
use App\Http\Requests\RollBackRequest;


class RollBackController extends Controller
{
    private $rollBackService;

    public function __construct(RollBackService $rollBackService)
    {
        $this->rollBackService = $rollBackService;
    }

    public function index()
    {
        $rollbacks = $this->rollBackService->getAllRollBacks();
        return RollBackResource::collection($rollbacks);
    }

    public function getRollBacksPendientes()
    {
        $rollbacks = $this->rollBackService->getRollBacksPendientes();
        return RollBackResource::collection($rollbacks);
    }

    public function autorizarRollback($rollBackHistoricoId)
    {
        $this->rollBackService->autorizarRollback($rollBackHistoricoId);
        return response()->json(['message' => 'Rollback autorizado exitosamente.'], 200);
    }

    public function solicitarRollback(RollBackRequest $request, $id)
    {
        $datos = $request->all();
        $resultado = $this->rollBackService->solicitarRollback($id, $datos);
        if ($resultado) {
            return response()->json(['message' => 'Rollback solicitado exitosamente.'], 200);
        } else {
            return response()->json(['message' => 'Error al solicitar el rollback.'], 500);
        }
    }
}
