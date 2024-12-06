<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInversionRequest;
use Illuminate\Http\Request;

use App\Services\InversionService;
use App\Services\CuotaInversionService;
use App\Http\Resources\Cuota as CuotaResource;
use App\Http\Resources\Inversion as InversionResource;

class InversionController extends Controller
{
    private $inversionService;

    private $cuotaInversionService;

    public function __construct(InversionService $inversionService, CuotaInversionService $cuotaInversionService)
    {
        $this->inversionService = $inversionService;
        $this->cuotaInversionService = $cuotaInversionService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inversiones = $this->inversionService->getInversiones();
        return InversionResource::collection($inversiones);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInversionRequest $request)
    {
        $this->inversionService->createInversion($request->all());
        return response()->json(['message' => 'Inversion created successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $inversion = $this->inversionService->getInversion($id);
        return new InversionResource($inversion);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $inversion = $this->inversionService->getInversion($id);
        $this->inversionService->updateInversion($inversion, $request->all());
        return response()->json(['message' => 'Inversion updated successfully'], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $inversion = $this->inversionService->getInversion($id);
        $this->inversionService->deleteInversion($inversion);
        return response()->json(['message' => 'Inversion deleted successfully'], 200);
    }

    public function cuotas(string $id)
    {
        $inversion = $this->inversionService->getInversion($id);
        $cuotas = $this->cuotaInversionService->getCuotasInversion($inversion);
        return CuotaResource::collection($cuotas);
    }


}
