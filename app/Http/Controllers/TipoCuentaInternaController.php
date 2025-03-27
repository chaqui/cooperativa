<?php

namespace App\Http\Controllers;

use App\Traits\Loggable;
use App\Http\Resources\Caja as CajaResource;
use App\Services\TipoCuentaInternaService;
use App\Http\Requests\TipoCuentaInternaRequest;
use App\Http\Resources\Retiro as RetiroResource;
use App\Http\Resources\Deposito as DepositoResource;
use App\Http\Resources\TipoCuentaInterna as TipoCuentaInternaResource;




class TipoCuentaInternaController extends Controller
{
    use Loggable;
    private TipoCuentaInternaService $tipoCuentaInternaService;

    public function __construct(TipoCuentaInternaService $tipoCuentaInternaService)
    {
        $this->tipoCuentaInternaService = $tipoCuentaInternaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return TipoCuentaInternaResource::collection($this->tipoCuentaInternaService->getAll());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TipoCuentaInternaRequest $request)
    {
        $tipoCuentaInterna = $this->tipoCuentaInternaService->create($request->all());
        return new TipoCuentaInternaResource($tipoCuentaInterna);
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tipoCuentaInterna = $this->tipoCuentaInternaService->getById($id);
        return new TipoCuentaInternaResource($tipoCuentaInterna);
    }

    public function getDetalles(string $id)
    {

        $cuentas = $this->tipoCuentaInternaService->getCuentas($id);
        return CajaResource::collection($cuentas);
    }

    public function getDepositos(string $id)
    {
        $depositos = $this->tipoCuentaInternaService->getDepositos($id);
        return DepositoResource::collection($depositos);
    }

    public function getRetiros(string $id)
    {
        $retiros = $this->tipoCuentaInternaService->getRetiros($id);
        return RetiroResource::collection($retiros);
    }


}
