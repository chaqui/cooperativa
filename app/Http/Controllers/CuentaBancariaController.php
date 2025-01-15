<?php

namespace App\Http\Controllers;

use App\Services\CuentaBancariaService;
use Illuminate\Http\Request;
use App\Http\Resources\CuentaBancaria as CuentaBancariaResource;

class CuentaBancariaController extends Controller
{

    private CuentaBancariaService $cuentaBancariaService;

    public function __construct(CuentaBancariaService $cuentaBancariaService)
    {
        $this->cuentaBancariaService = $cuentaBancariaService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cuentasBancarias = $this->cuentaBancariaService->getCuentasBancarias();
        return CuentaBancariaResource::collection($cuentasBancarias);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->cuentaBancariaService->createCuentaBancaria($request->all());
        return response()->json(['message' => 'Cuenta Bancaria created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $cuantaBancaria = $this->cuentaBancariaService->getCuentaBancaria($id);
        return new CuentaBancariaResource($cuantaBancaria);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $this->cuentaBancariaService->updateCuentaBancaria($request->all(), $id);
        return response()->json(['message' => 'Cuenta Bancaria updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->cuentaBancariaService->deleteCuentaBancaria($id);
        return response()->json(['message' => 'Cuenta Bancaria deleted successfully'], 200);
    }
}
