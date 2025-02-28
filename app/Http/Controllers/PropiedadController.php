<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\PropiedadRequest;
use App\Services\PropiedadService;
use App\Http\Resources\Propiedad as PropiedadResource;

class PropiedadController extends Controller
{
    private $propiedadService;

    public function __construct(PropiedadService $propiedadService)
    {
        $this->propiedadService = $propiedadService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $propiedades = $this->propiedadService->getPropiedades();
        return PropiedadResource::collection($propiedades);
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
    public function store(Request $request)
    {
        $propiedad = $this->propiedadService->createPropiedad($request->all());
        return new PropiedadResource($propiedad);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $propiedad = $this->propiedadService->getPropiedad($id);
        return new PropiedadResource($propiedad);
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
        $propiedad = $this->propiedadService->updatePropiedad($id, $request->all());
        return new PropiedadResource($propiedad);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $this->propiedadService->deletePropiedad($id);
        return response()->json(['message' => 'Propiedad deleted successfully'], 200);
    }
}
