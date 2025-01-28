<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReference;
use Illuminate\Http\Request;
use App\Services\ReferenceService;
use App\Http\Resources\Reference as ReferenceResource;

class ReferenceController extends Controller
{

    private $referenceService;

    public function __construct(ReferenceService $referenceService)
    {
        $this->referenceService = $referenceService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $references = $this->referenceService->getReferences();
        return ReferenceResource::collection($references);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReference $request)
    {
        $referenceData = $request->all();
        $reference = $this->referenceService->createReference($referenceData);
        return new ReferenceResource($reference);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reference = $this->referenceService->getReference($id);
        return new ReferenceResource($reference);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $referenceData = $request->all();
        $reference = $this->referenceService->getReference($id);
        $reference = $this->referenceService->updateReference($reference, $referenceData);
        return new ReferenceResource($reference);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $reference = $this->referenceService->getReference($id);
        $reference->delete();
        return response()->json(null, 204);
    }
}
